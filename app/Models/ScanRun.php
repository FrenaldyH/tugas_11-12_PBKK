<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Progress and result of one background workspace scan.
class ScanRun extends Model
{
    protected $fillable = [
        'status', 'total', 'processed', 'current_file', 'hashes', 'findings', 'changed', 'error',
        'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'hashes' => 'array',
            'findings' => 'array',
            'changed' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Still waiting in the queue or being processed by the worker
    public function isActive(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    // 0-100 for the progress bar
    public function percent(): int
    {
        if ($this->status === 'done') {
            return 100;
        }

        return $this->total > 0 ? (int) floor($this->processed * 100 / $this->total) : 0;
    }
}
