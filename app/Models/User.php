<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Chat history sent by this user to the LLM API
    public function chatLogs(): HasMany
    {
        return $this->hasMany(ChatLog::class);
    }

    // Workspace chat sessions (Livewire)
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    // Every bubble this user owns, across all conversations
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    // Background workspace scans started by this user
    public function scanRuns(): HasMany
    {
        return $this->hasMany(ScanRun::class);
    }
}
