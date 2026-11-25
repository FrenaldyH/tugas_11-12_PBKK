<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One chat bubble: a user prompt or an AI answer.
class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'role', 'content', 'status', 'provider', 'model', 'total_tokens'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
}
