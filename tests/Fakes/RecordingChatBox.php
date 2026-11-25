<?php

namespace Tests\Fakes;

use App\Livewire\ChatBox;

// ChatBox that records wire:stream calls instead of writing them to the response,
// so tests can check what the browser would receive piece by piece.
class RecordingChatBox extends ChatBox
{
    public static array $streamed = [];

    public function stream($content = null, $replace = false, $name = null, $el = null, $ref = null, $to = null)
    {
        static::$streamed[] = ['name' => $name ?? $to, 'content' => $content, 'replace' => $replace];

        return null;
    }

    // Everything streamed into one wire:stream target, joined
    public static function streamedTo(string $name): string
    {
        return collect(static::$streamed)->where('name', $name)->pluck('content')->implode('');
    }
}
