<?php

namespace App\Services;

use Generator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

// Talks to OpenAI-compatible Chat Completion servers (Senopati, local Ollama) with retry + fallback.
class LlmClient
{
    // Message of the fake outage used by the playground's "simulate primary down" switch
    private const SIMULATED = 'Simulasi: provider utama dimatikan dari playground';

    // Providers tried during the last chat()/stream() call: [provider, ok, reason]
    private array $attempts = [];
    // Build the messages array: theme system prompt first, then the user prompt.
    public function messages(string $prompt, string $theme = 'default'): array
    {
        return $this->conversation([['role' => 'user', 'content' => $prompt]], $theme);
    }

    // Multi-turn chat (Livewire workspace): theme system prompt first, then earlier turns in order.
    // $turns: [['role' => 'user'|'assistant', 'content' => '...'], ...], ending with the newest user prompt.
    public function conversation(array $turns, string $theme = 'default'): array
    {
        $system = config("llm.themes.$theme") ?? config('llm.themes.default');

        return [['role' => 'system', 'content' => $system], ...$turns];
    }

    // Providers tried during the last call, in order, so the UI can show the fallback path.
    public function attempts(): array
    {
        return $this->attempts;
    }

    // Wait for the full answer. Returns provider, model, content and token usage.
    // $simulatePrimaryDown makes the first provider fail on purpose to demo the fallback.
    public function chat(array $messages, bool $think, bool $simulatePrimaryDown = false): array
    {
        $this->attempts = [];
        $providers = $this->providers();

        foreach ($providers as $name => $provider) {
            $startedAt = microtime(true);
            try {
                if ($simulatePrimaryDown && $name === array_key_first($providers)) {
                    throw new ConnectionException(self::SIMULATED);
                }

                $json = $this->request($provider)
                    ->retry(config('llm.retries'), 200, fn (Throwable $e) => $this->isTransient($e))
                    ->post('/chat/completions', $this->payload($provider, $messages, false, $think))
                    ->throw() // retry() only throws itself when tries > 1
                    ->json();
                $this->attempts[] = ['provider' => $name, 'ok' => true, 'reason' => null];

                return [
                    'provider' => $name,
                    'model' => $json['model'] ?? $provider['model'],
                    'content' => $json['choices'][0]['message']['content'] ?? '',
                    'usage' => $json['usage'] ?? [],
                ];
            } catch (Throwable $e) {
                $this->fail($name, $e, $startedAt);
            }
        }

        throw new RuntimeException('Semua server AI tidak dapat dihubungi.');
    }

    // Stream the answer. Yields event => value pairs:
    //   "attempt"  => [provider, ok, reason] for every provider tried (the fallback path),
    //   "thinking" => '' once when the model starts reasoning,
    //   "token"    => piece for each answer piece.
    // The generator's return value holds provider/model/usage.
    // Fallback only happens before the first piece arrives; a mid-stream failure is thrown.
    public function stream(array $messages, bool $think, bool $simulatePrimaryDown = false): Generator
    {
        $this->attempts = [];
        $providers = $this->providers();

        foreach ($providers as $name => $provider) {
            $startedAt = microtime(true);
            try {
                if ($simulatePrimaryDown && $name === array_key_first($providers)) {
                    throw new ConnectionException(self::SIMULATED);
                }

                $response = $this->request($provider)
                    ->withOptions(['stream' => true])
                    ->post('/chat/completions', $this->payload($provider, $messages, true, $think))
                    ->throw();
            } catch (Throwable $e) {
                yield 'attempt' => $this->fail($name, $e, $startedAt);

                continue;
            }

            $this->attempts[] = ['provider' => $name, 'ok' => true, 'reason' => null];
            yield 'attempt' => end($this->attempts);

            $usage = yield from $this->readSse($response->toPsrResponse()->getBody());

            return ['provider' => $name, 'model' => $provider['model'], 'usage' => $usage];
        }

        throw new RuntimeException('Semua server AI tidak dapat dihubungi.');
    }

    // Parse "data: {...}" lines from an SSE body, yield events (see stream()), return usage.
    private function readSse($body): Generator
    {
        $buffer = '';
        $usage = [];
        $thinking = false;

        while (! $body->eof()) {
            $buffer .= $body->read(1024);

            // Handle every complete line; keep a partial line in the buffer for the next read
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                if ($data === '[DONE]') {
                    return $usage;
                }

                $chunk = json_decode($data, true) ?? [];
                $usage = $chunk['usage'] ?? $usage;
                $delta = $chunk['choices'][0]['delta'] ?? [];

                // Signal reasoning once; its text is never forwarded (no chain-of-thought to the user)
                if (! $thinking && ($delta['reasoning_content'] ?? '') !== '') {
                    $thinking = true;
                    yield 'thinking' => '';
                }
                if (($delta['content'] ?? '') !== '') {
                    yield 'token' => $delta['content'];
                }
            }
        }

        return $usage;
    }

    // Providers in fallback order, skipping ones without a URL or model configured.
    private function providers(): array
    {
        $all = config('llm.providers');

        return collect(config('llm.order'))
            ->mapWithKeys(fn ($name) => [$name => $all[$name] ?? null])
            ->filter(fn ($p) => ! empty($p['base_url']) && ! empty($p['model']))
            ->all();
    }

    // Base HTTP request for one provider.
    private function request(array $provider): PendingRequest
    {
        return Http::baseUrl(rtrim($provider['base_url'], '/'))
            ->timeout(config('llm.timeout'))
            ->acceptJson()
            ->when($provider['api_key'], fn ($http, $key) => $http->withToken($key));
    }

    // Chat Completion request body.
    private function payload(array $provider, array $messages, bool $stream, bool $think): array
    {
        return array_filter([
            'model' => $provider['model'],
            'messages' => $messages,
            'temperature' => config('llm.temperature'),
            // qwen3 "thinks" before answering unless told not to; the gateway forwards this flag
            'think' => $think,
            'stream' => $stream,
            // Ask for token usage in the final stream chunk
            'stream_options' => $stream ? ['include_usage' => true] : null,
        ], fn ($v) => $v !== null);
    }

    // Log a failed provider (full message) and record a short, safe reason for the client.
    private function fail(string $name, Throwable $e, float $startedAt): array
    {
        Log::warning("LLM provider [$name] failed, trying next: ".$e->getMessage());
        $reason = $this->reason($e, microtime(true) - $startedAt);

        return $this->attempts[] = ['provider' => $name, 'ok' => false, 'reason' => $reason];
    }

    // Short reason shown to the user; raw error text (URLs, gateway messages) stays in the log only.
    private function reason(Throwable $e, float $seconds): string
    {
        $timeout = config('llm.timeout');

        return match (true) {
            $e->getMessage() === self::SIMULATED => 'disimulasikan mati',
            $e instanceof RequestException => 'HTTP '.$e->response->status(),
            // Stream mode reports a timeout as "Connection refused", so also judge by how long it took
            $e instanceof ConnectionException && ($this->timedOut($e) || $seconds >= $timeout * 0.9) => "timeout $timeout s",
            $e instanceof ConnectionException => 'tidak dapat dihubungi',
            default => 'error',
        };
    }

    // cURL reports "Operation timed out after N milliseconds".
    private function timedOut(Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'timed out');
    }

    // Retry only errors that may heal quickly: dropped connection or 5xx. A 4xx will fail again, and a
    // timeout already used the whole LLM_TIMEOUT, so retrying it would multiply the wait; fall back instead.
    private function isTransient(Throwable $e): bool
    {
        return ($e instanceof ConnectionException && ! $this->timedOut($e))
            || ($e instanceof RequestException && $e->response->serverError());
    }
}
