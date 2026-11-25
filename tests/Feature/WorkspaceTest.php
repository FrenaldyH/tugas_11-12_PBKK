<?php

namespace Tests\Feature;

use App\Jobs\ScanWorkspaceFiles;
use App\Livewire\ChatBox;
use App\Livewire\CodePanel;
use App\Livewire\ConversationSidebar;
use App\Livewire\ScanMonitor;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\ScanRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\Fakes\RecordingChatBox;
use Tests\TestCase;

// Pertemuan 12: Livewire workspace (session auth, events between components, DB history, challenges).
// LLM servers are faked with Http::fake(), so no Ollama/Senopati is needed.
class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'llm.order' => ['ollama', 'senopati'],
            'llm.retries' => 1,
            'llm.providers.ollama.base_url' => 'http://ollama.test/v1',
            'llm.providers.ollama.model' => 'qwen3:14b',
            'llm.providers.senopati.base_url' => 'https://senopati.test/v1',
            'llm.providers.senopati.model' => 'qwen3:14b',
            'llm.providers.senopati.api_key' => 'its-secret',
            'scan.delay_ms' => 0,
        ]);
        RecordingChatBox::$streamed = [];

        $this->user = User::factory()->create();
    }

    // A streamed Chat Completion answer split into the given pieces
    private function sse(string ...$pieces): string
    {
        $body = '';
        foreach ($pieces as $piece) {
            $body .= 'data: '.json_encode(['choices' => [['delta' => ['content' => $piece]]]])."\n\n";
        }

        return $body.'data: '.json_encode(['choices' => [], 'usage' => ['total_tokens' => 42]])."\n\ndata: [DONE]\n\n";
    }

    private function conversationFor(User $user, string $title = 'Belajar Livewire'): Conversation
    {
        return $user->conversations()->create(['title' => $title, 'theme' => 'default']);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/workspace');
        $this->get('/workspace')->assertRedirect(route('login'));
    }

    public function test_workspace_renders_all_components_and_reopens_latest_conversation(): void
    {
        $this->conversationFor($this->user, 'Lama');
        $this->travel(1)->minutes();
        $latest = $this->conversationFor($this->user, 'Terbaru');

        $this->actingAs($this->user)->get('/workspace')
            ->assertOk()
            ->assertSeeLivewire(ConversationSidebar::class)
            ->assertSeeLivewire(ChatBox::class)
            ->assertSeeLivewire(CodePanel::class)
            ->assertDontSeeLivewire(ScanMonitor::class) // the project scan has its own page
            ->assertViewHas('conversationId', $latest->id);
    }

    public function test_sending_saves_user_bubble_then_streams_and_saves_answer(): void
    {
        Http::fake(['ollama.test/*' => Http::response($this->sse('Halo ', '<b>dunia</b>'), 200, ['Content-Type' => 'text/event-stream'])]);

        $chat = Livewire::actingAs($this->user)->test(RecordingChatBox::class)
            ->set('theme', 'prompt_engineer')
            ->set('prompt', 'Sapa aku dong')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('prompt', '')
            ->assertSet('waiting', true)
            ->assertDispatched('conversation-saved');

        // Request 1 already saved the prompt and created the conversation, before the LLM was called
        $conversation = $this->user->conversations()->sole();
        $this->assertSame('Sapa aku dong', $conversation->title);
        $this->assertSame('prompt_engineer', $conversation->theme);
        $this->assertDatabaseHas('chat_messages', ['conversation_id' => $conversation->id, 'user_id' => $this->user->id, 'role' => 'user']);
        Http::assertNothingSent();

        $chat->call('answer');

        // Pieces were streamed one by one and escaped (wire:stream inserts HTML)
        $this->assertSame('Halo &lt;b&gt;dunia&lt;/b&gt;', RecordingChatBox::streamedTo('answer'));

        $chat->assertSet('waiting', false)->assertDispatched('answer-saved');

        $answer = ChatMessage::where('role', 'assistant')->sole();
        $this->assertSame('Halo <b>dunia</b>', $answer->content);
        $this->assertSame('success', $answer->status);
        $this->assertSame('ollama', $answer->provider);
        $this->assertSame(42, $answer->total_tokens);
        $this->assertSame($this->user->id, $answer->user_id);

        // System prompt of the chosen theme + the user's prompt were sent
        Http::assertSent(fn (Request $r) => $r['messages'][0]['content'] === config('llm.themes.prompt_engineer')
            && $r['messages'][1]['content'] === 'Sapa aku dong'
            && $r['stream'] === true);

        // The saved answer is rendered as escaped Markdown: the model cannot inject HTML into the page
        $chat->assertSeeHtml('Halo &lt;b&gt;dunia&lt;/b&gt;')->assertDontSeeHtml('<b>dunia</b>');
    }

    public function test_earlier_turns_are_sent_as_context(): void
    {
        $conversation = $this->conversationFor($this->user);
        $conversation->messages()->create(['user_id' => $this->user->id, 'role' => 'user', 'content' => 'Nama saya Dimas']);
        $conversation->messages()->create(['user_id' => $this->user->id, 'role' => 'assistant', 'content' => 'Halo Dimas']);
        Http::fake(['ollama.test/*' => Http::response($this->sse('Dimas'), 200, ['Content-Type' => 'text/event-stream'])]);

        $chat = Livewire::actingAs($this->user)->test(ChatBox::class, ['conversationId' => $conversation->id])
            ->set('prompt', 'Siapa nama saya?')
            ->call('send');
        $chat->call('answer');

        Http::assertSent(fn (Request $r) => array_column($r['messages'], 'role') === ['system', 'user', 'assistant', 'user']
            && $r['messages'][1]['content'] === 'Nama saya Dimas');
        $this->assertSame(1, Conversation::count()); // same conversation, no new one
    }

    public function test_prompt_is_validated_and_counter_updates_while_typing(): void
    {
        Livewire::actingAs($this->user)->test(ChatBox::class)
            ->set('prompt', '')
            ->call('send')
            ->assertHasErrors(['prompt' => 'required'])
            ->set('prompt', 'ab')
            ->call('send')
            ->assertHasErrors(['prompt' => 'min'])
            ->set('prompt', str_repeat('a', 1001))
            ->assertSet('sisaKarakter', -1)
            ->assertHasErrors(['prompt' => 'max']) // updatedPrompt() warns before sending
            ->set('prompt', 'abc')
            ->assertSet('sisaKarakter', 997)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_second_send_while_waiting_is_ignored(): void
    {
        $chat = Livewire::actingAs($this->user)->test(ChatBox::class)
            ->set('prompt', 'Pertanyaan pertama')
            ->call('send')
            ->set('prompt', 'Klik ganda')
            ->call('send');

        $this->assertSame(1, ChatMessage::count());
        $chat->assertSet('prompt', 'Klik ganda');
    }

    public function test_both_providers_down_saves_failed_answer_without_crashing(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $chat = Livewire::actingAs($this->user)->test(ChatBox::class)
            ->set('prompt', 'Halo AI')
            ->call('send');
        $chat->call('answer');

        $chat->assertSet('waiting', false)->assertSee('Koneksi ke server AI terputus');
        $answer = ChatMessage::where('role', 'assistant')->sole();
        $this->assertSame('failed', $answer->status);
        $this->assertNull($answer->provider);
    }

    public function test_falls_back_to_senopati_when_ollama_is_down(): void
    {
        Http::fake([
            'ollama.test/*' => fn () => throw new ConnectionException('refused'),
            'senopati.test/*' => Http::response($this->sse('Dari Senopati'), 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $chat = Livewire::actingAs($this->user)->test(RecordingChatBox::class)->set('prompt', 'Halo AI')->call('send');
        $chat->call('answer');

        $this->assertStringContainsString('ollama gagal (tidak dapat dihubungi)', RecordingChatBox::streamedTo('status'));
        $this->assertSame('senopati', ChatMessage::where('role', 'assistant')->sole()->provider);
    }

    public function test_conversations_of_another_user_cannot_be_opened(): void
    {
        $other = User::factory()->create();
        $foreign = $this->conversationFor($other, 'Rahasia orang lain');
        $foreign->messages()->create(['user_id' => $other->id, 'role' => 'user', 'content' => 'isi rahasia']);

        // Not listed in the sidebar
        Livewire::actingAs($this->user)->test(ConversationSidebar::class)->assertDontSee('Rahasia orang lain');

        // Not openable through the event payload, the sidebar buttons or the code panel: 404
        $messageId = $foreign->messages()->first()->id;
        Livewire::actingAs($this->user)->test(ChatBox::class)->call('open', $foreign->id)->assertNotFound();
        Livewire::actingAs($this->user)->test(ConversationSidebar::class)->call('select', $foreign->id)->assertNotFound();
        Livewire::actingAs($this->user)->test(ConversationSidebar::class)->call('delete', $foreign->id)->assertNotFound();
        Livewire::actingAs($this->user)->test(CodePanel::class)->call('show', $messageId)->assertNotFound();

        $this->assertModelExists($foreign);
    }

    public function test_conversation_id_cannot_be_changed_from_the_browser(): void
    {
        $foreign = $this->conversationFor(User::factory()->create());

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::actingAs($this->user)->test(ChatBox::class)->set('conversationId', $foreign->id);
    }

    public function test_sidebar_selects_starts_new_and_deletes_with_events(): void
    {
        $first = $this->conversationFor($this->user, 'Pertama');
        $second = $this->conversationFor($this->user, 'Kedua');
        $second->messages()->create(['user_id' => $this->user->id, 'role' => 'user', 'content' => 'x']);

        Livewire::actingAs($this->user)->test(ConversationSidebar::class)
            ->assertSee(['Pertama', 'Kedua'])
            ->set('search', 'Kedu')
            ->assertSee('Kedua')->assertDontSee('Pertama')
            ->set('search', '')
            ->call('select', $first->id)
            ->assertSet('activeId', $first->id)
            ->assertDispatched('conversation-selected', id: $first->id)
            ->call('delete', $first->id)
            ->assertSet('activeId', null)
            ->assertDispatched('conversation-selected', id: null)
            ->dispatch('conversation-saved', id: $second->id) // event coming from ChatBox
            ->assertSet('activeId', $second->id);

        $this->assertModelMissing($first);
        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_chat_box_reacts_to_conversation_selected_event(): void
    {
        $conversation = $this->conversationFor($this->user, 'Topik A');
        $conversation->update(['theme' => 'evaluator']);
        $conversation->messages()->create(['user_id' => $this->user->id, 'role' => 'user', 'content' => 'Pesan di topik A']);

        Livewire::actingAs($this->user)->test(ChatBox::class)
            ->assertSee('satu instruksi.')
            ->dispatch('conversation-selected', id: $conversation->id)
            ->assertSet('conversationId', $conversation->id)
            ->assertSet('theme', 'evaluator')
            ->assertSee('Pesan di topik A')
            ->dispatch('conversation-selected', id: null)
            ->assertSet('conversationId', null)
            ->assertDontSee('Pesan di topik A');
    }

    public function test_code_panel_shows_code_blocks_of_answers(): void
    {
        $conversation = $this->conversationFor($this->user);
        $withCode = $conversation->messages()->create([
            'user_id' => $this->user->id, 'role' => 'assistant',
            'content' => "Contoh:\n\n```php\necho 'hai';\n```\n\n```html\n<p>x</p>\n```",
        ]);
        $plain = $conversation->messages()->create(['user_id' => $this->user->id, 'role' => 'assistant', 'content' => 'Tanpa kode']);

        Livewire::actingAs($this->user)->test(CodePanel::class, ['conversationId' => $conversation->id])
            ->assertSet('messageId', $withCode->id)
            ->assertSee(['blok-1.php', 'blok-2.html'])
            ->assertSeeHtml('class="language-php"')
            ->assertSeeHtml('&lt;p&gt;x&lt;/p&gt;') // code is escaped, highlight.js colors it in the browser
            ->dispatch('answer-saved', id: $plain->id)
            ->assertSet('messageId', $withCode->id) // an answer without code keeps the previous code
            ->dispatch('conversation-selected', id: null)
            ->assertSet('messageId', null)
            ->assertSee('Belum ada kode');
    }

    public function test_scan_monitor_queues_job_and_polls_only_while_active(): void
    {
        Queue::fake();

        $monitor = Livewire::actingAs($this->user)->test(ScanMonitor::class)
            ->assertDontSeeHtml('wire:poll.1s')
            ->call('start')
            ->assertSeeHtml('wire:poll.1s="cekStatus"')
            ->assertSee('Menunggu queue worker');

        Queue::assertPushed(ScanWorkspaceFiles::class);
        $run = ScanRun::sole();
        $this->assertSame('queued', $run->status);

        $monitor->call('start'); // already active: no second scan
        Queue::assertPushed(ScanWorkspaceFiles::class, 1);

        $run->update(['status' => 'done', 'total' => 3, 'processed' => 3, 'findings' => [], 'changed' => []]);
        $monitor->call('cekStatus')->assertDontSeeHtml('wire:poll.1s')->assertSee('Selesai');
    }

    public function test_scan_job_hashes_files_finds_secrets_and_detects_changes(): void
    {
        $root = storage_path('framework/testing/scan-fixture');
        File::deleteDirectory($root);
        File::ensureDirectoryExists("$root/app");
        File::put("$root/app/Clean.php", "<?php\n\$key = env('SENOPATI_API_KEY');\n");
        File::put("$root/app/Leaky.php", "<?php\n\n\$config = ['api_key' => 'sk-abcdefghijklmnopqrstuvwxyz'];\n");
        File::put("$root/app/image.png", 'not scanned');
        config(['scan.root' => $root, 'scan.paths' => ['app']]);

        $first = $this->user->scanRuns()->create(['status' => 'queued']);
        (new ScanWorkspaceFiles($first->id))->handle();
        $first->refresh();

        $this->assertSame('done', $first->status);
        $this->assertSame(2, $first->total);
        $this->assertSame(2, $first->processed);
        $this->assertSame(100, $first->percent());
        $this->assertSame(hash_file('sha256', "$root/app/Clean.php"), $first->hashes['app/Clean.php']);
        $this->assertEqualsCanonicalizing(
            [['file' => 'app/Leaky.php', 'line' => 3, 'rule' => 'sk_prefixed_key'], ['file' => 'app/Leaky.php', 'line' => 3, 'rule' => 'hardcoded_credential']],
            $first->findings,
        );
        $this->assertSame([], $first->changed); // nothing to compare with yet

        File::put("$root/app/Clean.php", "<?php\n// edited\n");
        File::delete("$root/app/Leaky.php");
        File::put("$root/app/New.php", "<?php\n");

        $second = $this->user->scanRuns()->create(['status' => 'queued']);
        (new ScanWorkspaceFiles($second->id))->handle();

        $this->assertEqualsCanonicalizing([
            ['file' => 'app/Clean.php', 'change' => 'berubah'],
            ['file' => 'app/New.php', 'change' => 'baru'],
            ['file' => 'app/Leaky.php', 'change' => 'dihapus'],
        ], $second->fresh()->changed);

        File::deleteDirectory($root);
    }

    public function test_scan_has_its_own_page(): void
    {
        $this->get('/scan')->assertRedirect(route('login'));

        $this->actingAs($this->user)->get('/scan')
            ->assertOk()
            ->assertSeeLivewire(ScanMonitor::class)
            ->assertDontSeeLivewire(ChatBox::class)
            ->assertSee(['app/', 'config/', 'routes/']);
    }

    public function test_asking_the_ai_about_findings_opens_a_new_conversation_with_the_prompt_typed_in(): void
    {
        $latest = $this->conversationFor($this->user, 'Percakapan lama');
        $this->user->scanRuns()->create([
            'status' => 'done', 'total' => 5, 'processed' => 5, 'changed' => [],
            'findings' => [['file' => 'config/x.php', 'line' => 7, 'rule' => 'hardcoded_credential']],
        ]);

        Livewire::actingAs($this->user)->test(ScanMonitor::class)
            ->call('askAi')
            ->assertRedirect(route('workspace'));
        $this->assertStringContainsString('config/x.php:7', session(ChatBox::PREFILL));

        // The workspace starts a new conversation instead of reopening the latest one
        $this->actingAs($this->user)->get('/workspace')->assertViewHas('conversationId', null);

        // and the chat box has the prompt typed in, not sent
        Livewire::actingAs($this->user)->test(ChatBox::class)
            ->assertSet('conversationId', null)
            ->assertSet('prompt', fn ($prompt) => str_contains($prompt, 'config/x.php:7'))
            ->assertSet('sisaKarakter', fn ($left) => $left < 1000);
        $this->assertDatabaseCount('chat_messages', 0);
        $this->assertModelExists($latest);
    }
}
