<?php

namespace App\Livewire;

use App\Livewire\Concerns\OwnsConversations;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

// Left panel: the user's conversations. Talks to ChatBox and CodePanel only through events:
//   dispatches "conversation-selected" {id|null}; listens to "conversation-saved" {id} from ChatBox.
class ConversationSidebar extends Component
{
    use OwnsConversations;

    // Highlighted item. Locked: only server code may change it, never a request from the browser.
    #[Locked]
    public ?int $activeId = null;

    public string $search = '';

    public function mount(?int $conversationId = null): void
    {
        $this->activeId = $conversationId;
    }

    // Open a conversation in the chat box (and its code in the code panel)
    public function select(int $id): void
    {
        $this->activeId = $this->ownedConversation($id)->id;
        $this->dispatch('conversation-selected', id: $this->activeId);
    }

    public function startNew(): void
    {
        $this->activeId = null;
        $this->dispatch('conversation-selected', id: null);
    }

    // Messages are removed with it (cascadeOnDelete)
    public function delete(int $id): void
    {
        $this->ownedConversation($id)->delete();

        if ($this->activeId === $id) {
            $this->startNew();
        }
    }

    // ChatBox created a conversation or added a message to it: highlight it. The re-render that follows
    // reloads the list, so a new title appears and the active conversation moves to the top.
    #[On('conversation-saved')]
    public function saved(int $id): void
    {
        $this->activeId = $id;
    }

    // Newest activity first. Only 50 rows so the list stays light; older ones are found with the search box.
    #[Computed]
    public function conversations(): Collection
    {
        $search = trim($this->search);

        return $this->user()->conversations()
            ->withCount('messages')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('updated_at')
            ->latest('id')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.conversation-sidebar');
    }
}
