<?php

namespace App\Livewire\Concerns;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;

// Every id coming from the browser (event payloads, wire:click arguments) can be edited by the user,
// so each lookup goes through the logged-in user's own relation: someone else's id gives a 404.
trait OwnsConversations
{
    protected function user(): User
    {
        return auth()->user();
    }

    protected function ownedConversation(int $id): Conversation
    {
        return $this->user()->conversations()->findOrFail($id);
    }

    protected function ownedMessage(int $id): ChatMessage
    {
        return $this->user()->chatMessages()->findOrFail($id);
    }
}
