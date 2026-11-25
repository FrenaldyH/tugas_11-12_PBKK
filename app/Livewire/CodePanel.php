<?php

namespace App\Livewire;

use App\Livewire\Concerns\OwnsConversations;
use App\Models\ChatMessage;
use App\Support\Markdown;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

// Right panel (Challenge 2): code blocks of an AI answer, one tab per block, highlighted by highlight.js.
// Listens to "conversation-selected" (sidebar), "answer-saved" (chat box) and "show-code" (bubble button).
class CodePanel extends Component
{
    use OwnsConversations;

    #[Locked]
    public ?int $messageId = null;

    public function mount(?int $conversationId = null): void
    {
        $this->conversationSelected($conversationId);
    }

    // Show the newest answer with code in that conversation (empty panel for a new conversation)
    #[On('conversation-selected')]
    public function conversationSelected(?int $id = null): void
    {
        $this->messageId = $id
            ? $this->ownedConversation($id)->messages()
                ->where('role', 'assistant')
                ->where('content', 'like', '%```%')
                ->reorder('id', 'desc')
                ->value('id')
            : null;
    }

    // A new answer arrived: switch to it only when it contains code, otherwise keep the previous code
    #[On('answer-saved')]
    public function answerSaved(int $id): void
    {
        $message = $this->ownedMessage($id);

        if (Markdown::codeBlocks($message->content) !== []) {
            $this->messageId = $message->id;
        }
    }

    // "Buka di panel kode" button on a bubble
    #[On('show-code')]
    public function show(int $id): void
    {
        $this->messageId = $this->ownedMessage($id)->id;
    }

    #[Computed]
    public function message(): ?ChatMessage
    {
        return $this->messageId ? $this->ownedMessage($this->messageId) : null;
    }

    // [['lang', 'code', 'name'], ...]
    #[Computed]
    public function blocks(): array
    {
        if (! $this->message) {
            return [];
        }

        return collect(Markdown::codeBlocks($this->message->content))
            ->map(fn ($block, $i) => $block + ['name' => 'blok-'.($i + 1).'.'.Markdown::extension($block['lang'])])
            ->all();
    }

    public function render()
    {
        return view('livewire.code-panel');
    }
}
