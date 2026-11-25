<?php

namespace App\Livewire;

use App\Livewire\Concerns\OwnsConversations;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\LlmClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

// Main panel: chat bubbles + prompt box. Sending is split into two requests:
//   1. send()   saves the user bubble, clears the box and shows a "waiting" bubble immediately;
//   2. answer() calls the LLM and streams the answer into that bubble (wire:stream), then saves it.
// Events: listens to "conversation-selected" {id|null} and "use-prompt" {text};
//         dispatches "conversation-saved" {id} (sidebar) and "answer-saved" {id} (code panel).
class ChatBox extends Component
{
    use OwnsConversations;

    // Same limit as the API (ChatAiRequest)
    public const MAX = 1000;

    // Bubbles kept on screen. Older ones stay in the database but are not loaded, so the page stays light.
    private const VISIBLE = 30;

    // Earlier bubbles sent to the model as context
    private const CONTEXT = 10;

    #[Locked]
    public ?int $conversationId = null;

    // True between send() and the end of answer(): the submit button stays disabled the whole time
    #[Locked]
    public bool $waiting = false;

    public string $prompt = '';

    public string $theme = 'default';

    public int $sisaKarakter = self::MAX;

    public function mount(?int $conversationId = null): void
    {
        $this->open($conversationId);
    }

    protected function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:3', 'max:'.self::MAX],
            'theme' => ['required', Rule::in(array_keys(config('llm.themes')))],
        ];
    }

    protected function messages(): array
    {
        return [
            'prompt.required' => 'Kotak prompt tidak boleh kosong.',
            'prompt.min' => 'Instruksi minimal 3 karakter.',
            'prompt.max' => 'Instruksi maksimal '.self::MAX.' karakter.',
            'theme.in' => 'Tema agent tidak dikenal.',
        ];
    }

    // Lifecycle hook: runs after every change of $prompt (wire:model.live) to update the counter and
    // warn as soon as the limit is passed, before the user presses send.
    public function updatedPrompt(): void
    {
        $this->sisaKarakter = self::MAX - mb_strlen($this->prompt);
        $this->validateOnly('prompt', ['prompt' => 'max:'.self::MAX]);
    }

    // The theme belongs to the conversation, so later answers keep using it
    public function updatedTheme(): void
    {
        $this->validateOnly('theme');

        if ($this->conversationId) {
            $this->ownedConversation($this->conversationId)->update(['theme' => $this->theme]);
        }
    }

    // Sidebar picked a conversation (null = start a new one)
    #[On('conversation-selected')]
    public function open(?int $id = null): void
    {
        $conversation = $id ? $this->ownedConversation($id) : null;

        $this->conversationId = $conversation?->id;
        $this->theme = $conversation->theme ?? 'default';
        $this->resetValidation();
        unset($this->bubbles, $this->conversation);
    }

    // Another panel suggests a prompt (e.g. "ask the AI about the scan result"); the user still presses send
    #[On('use-prompt')]
    public function usePrompt(string $text): void
    {
        $this->prompt = Str::limit($text, self::MAX, '');
        $this->updatedPrompt();
    }

    public function send(): void
    {
        // Second click/Enter while the AI is still answering
        if ($this->waiting) {
            return;
        }

        $this->validate();

        // Same budget as the API (throttle:20,1) so the LLM server cannot be flooded from the UI either
        $key = 'workspace-chat:'.auth()->id();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->addError('prompt', 'Terlalu banyak pesan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.');

            return;
        }
        RateLimiter::hit($key, 60);

        $conversation = DB::transaction(function () {
            $conversation = $this->conversationId
                ? $this->ownedConversation($this->conversationId)
                : $this->user()->conversations()->create([
                    'title' => Str::limit(Str::squish($this->prompt), 60),
                    'theme' => $this->theme,
                ]);

            $conversation->messages()->create([
                'user_id' => auth()->id(),
                'role' => 'user',
                'content' => $this->prompt,
            ]);
            $conversation->touch(); // moves it to the top of the sidebar

            return $conversation;
        });

        $this->conversationId = $conversation->id;
        $this->reset('prompt');
        $this->sisaKarakter = self::MAX;
        $this->waiting = true;
        unset($this->bubbles, $this->conversation);

        $this->dispatch('conversation-saved', id: $conversation->id);

        // Start request 2 right after this response is rendered
        $this->js('$wire.answer()');
    }

    // Ask the LLM (Ollama first, Senopati as fallback) and stream the answer into the waiting bubble.
    public function answer(LlmClient $llm): void
    {
        if (! $this->waiting || ! $this->conversationId) {
            return;
        }

        $conversation = $this->ownedConversation($this->conversationId);
        $text = '';

        try {
            $stream = $llm->stream($llm->conversation($this->context($conversation), $conversation->theme), (bool) config('llm.think'));

            foreach ($stream as $event => $value) {
                if ($event === 'token') {
                    if ($text === '') {
                        $this->stream('', replace: true, name: 'status');
                    }
                    $text .= $value;
                    // wire:stream inserts HTML, so every piece is escaped first
                    $this->stream(e($value), name: 'answer');
                } elseif ($event === 'attempt' && ! $value['ok']) {
                    $this->stream(e("{$value['provider']} gagal ({$value['reason']}), mencoba provider berikutnya…"), replace: true, name: 'status');
                } elseif ($event === 'thinking') {
                    $this->stream('Model sedang berpikir…', replace: true, name: 'status');
                }
            }
            $meta = $stream->getReturn();

            $message = $this->saveAnswer($conversation, [
                'content' => $text !== '' ? $text : '_(Model tidak mengirim jawaban.)_',
                'provider' => $meta['provider'],
                'model' => $meta['model'],
                'total_tokens' => $meta['usage']['total_tokens'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::warning('Workspace answer failed: '.$e->getMessage());

            // Keep what already arrived; otherwise explain the outage in the bubble instead of crashing
            $message = $this->saveAnswer($conversation, [
                'status' => 'failed',
                'content' => $text !== ''
                    ? $text."\n\n_(Jawaban terputus di tengah jalan.)_"
                    : 'Koneksi ke server AI terputus: Ollama lokal dan Senopati AI ITS tidak dapat dihubungi. Coba kirim lagi nanti.',
            ]);
        }

        $this->waiting = false;
        unset($this->bubbles, $this->conversation);

        $this->dispatch('conversation-saved', id: $conversation->id);
        $this->dispatch('answer-saved', id: $message->id);
    }

    // Last CONTEXT successful bubbles, oldest first, in Chat Completion format. Ends with the new prompt.
    private function context(Conversation $conversation): array
    {
        return $conversation->messages()
            ->where('status', 'success')
            ->reorder('id', 'desc')
            ->limit(self::CONTEXT)
            ->get(['role', 'content'])
            ->reverse()
            ->map(fn (ChatMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();
    }

    private function saveAnswer(Conversation $conversation, array $fields): ChatMessage
    {
        $message = $conversation->messages()->create(['user_id' => auth()->id(), 'role' => 'assistant'] + $fields);
        $conversation->touch();

        return $message;
    }

    #[Computed]
    public function conversation(): ?Conversation
    {
        return $this->conversationId ? $this->ownedConversation($this->conversationId) : null;
    }

    // Newest VISIBLE bubbles, shown oldest first. Queried here (not in render()) and cached per request.
    #[Computed]
    public function bubbles(): Collection
    {
        if (! $this->conversationId) {
            return collect();
        }

        return $this->user()->chatMessages()
            ->where('conversation_id', $this->conversationId)
            ->latest('id')
            ->limit(self::VISIBLE)
            ->get()
            ->reverse()
            ->values();
    }

    public function render()
    {
        return view('livewire.chat-box', [
            // key => label for every theme in config/llm.php
            'themes' => collect(config('llm.themes'))->keys()->mapWithKeys(fn ($key) => [$key => match ($key) {
                'default' => 'Workspace',
                'evaluator' => 'Evaluation',
                default => Str::headline($key),
            }]),
            'suggestions' => [
                ['title' => 'Buat komponen', 'prompt' => 'Buatkan komponen Livewire penghitung klik beserta view Blade-nya.'],
                ['title' => 'Jelaskan konsep', 'prompt' => 'Apa beda wire:model, wire:model.live, dan wire:model.blur?'],
                ['title' => 'Perbaiki prompt', 'prompt' => 'Perbaiki prompt ini: "dokumentasikan API login".'],
                ['title' => 'Rancang database', 'prompt' => 'Tulis migration Laravel untuk tabel produk dengan relasi kategori.'],
            ],
        ]);
    }
}
