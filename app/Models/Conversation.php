<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// One chat session in the workspace sidebar.
class Conversation extends Model
{
    protected $fillable = ['title', 'theme'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Bubbles in the order they were written
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }
}
