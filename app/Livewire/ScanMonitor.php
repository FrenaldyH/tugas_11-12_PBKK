<?php

namespace App\Livewire;

use App\Jobs\ScanWorkspaceFiles;
use App\Livewire\Concerns\OwnsConversations;
use App\Models\ScanRun;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

// Right panel, bottom (Challenge 3): starts a background scan on the queue and follows it with wire:poll.
// Polling only runs while a scan is queued/running; afterwards the panel stops asking the server.
class ScanMonitor extends Component
{
    use OwnsConversations;

    // A queued scan older than this probably means no queue worker is running
    private const STALE_SECONDS = 15;

    public function start(): void
    {
        if ($this->run?->isActive()) {
            return; // one scan per user at a time
        }

        $run = $this->user()->scanRuns()->create(['status' => 'queued']);
        ScanWorkspaceFiles::dispatch($run->id);

        unset($this->run);
    }

    // Called every second by wire:poll while a scan is active. The re-render reads the new progress.
    public function cekStatus(): void
    {
        unset($this->run);
    }

    // Fill the chat box with a request to explain the findings (the user still presses send)
    public function askAi(): void
    {
        $run = $this->run;
        if (! $run || $run->status !== 'done') {
            return;
        }

        $findings = collect($run->findings)->take(15)
            ->map(fn ($f) => "- {$f['file']}:{$f['line']} ({$f['rule']})")
            ->implode("\n");

        $this->dispatch('use-prompt', text: Str::limit(
            "Hasil scan {$run->total} file proyek Laravel saya menemukan ".count($run->findings).' kemungkinan kredensial yang ditulis langsung di kode:'
            ."\n$findings\n\nJelaskan risikonya dan cara memindahkannya ke .env.",
            1000,
        ));
    }

    #[Computed]
    public function run(): ?ScanRun
    {
        return $this->user()->scanRuns()->latest('id')->first();
    }

    // True when the job has been waiting too long: show a hint to start the worker
    public function stale(): bool
    {
        return $this->run?->status === 'queued'
            && $this->run->created_at->diffInSeconds(now()) > self::STALE_SECONDS;
    }

    public function render()
    {
        return view('livewire.scan-monitor');
    }
}
