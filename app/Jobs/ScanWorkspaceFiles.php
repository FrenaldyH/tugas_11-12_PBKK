<?php

namespace App\Jobs;

use App\Models\ScanRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;
use Throwable;

// Hashes every source file (SHA-256) and looks for hard-coded secrets, writing progress to scan_runs after
// each file. Runs on the queue worker; the ScanMonitor Livewire component polls the row with wire:poll.
class ScanWorkspaceFiles implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public int $scanRunId)
    {
    }

    public function handle(): void
    {
        $run = ScanRun::find($this->scanRunId);
        if (! $run || $run->status !== 'queued') {
            return;
        }

        $root = rtrim(config('scan.root'), '/\\');
        $files = $this->files($root);
        $run->update(['status' => 'running', 'total' => count($files), 'started_at' => now()]);

        $hashes = [];
        $findings = [];
        foreach ($files as $i => $path) {
            $relative = $this->relative($root, $path);
            $hashes[$relative] = hash_file('sha256', $path);

            foreach ($this->secrets($path) as [$line, $rule]) {
                $findings[] = ['file' => $relative, 'line' => $line, 'rule' => $rule];
            }

            $run->update(['processed' => $i + 1, 'current_file' => $relative]);
            if (config('scan.delay_ms') > 0) {
                usleep(config('scan.delay_ms') * 1000);
            }
        }

        $run->update([
            'status' => 'done',
            'current_file' => null,
            'hashes' => $hashes,
            'findings' => $findings,
            'changed' => $this->changedSincePrevious($run, $hashes),
            'finished_at' => now(),
        ]);
    }

    // Called by the queue when handle() throws or times out
    public function failed(Throwable $e): void
    {
        ScanRun::whereKey($this->scanRunId)->update([
            'status' => 'failed',
            'error' => Str::limit($e->getMessage(), 250),
            'finished_at' => now(),
        ]);
    }

    // Source files under the configured folders, sorted so the progress is predictable.
    private function files(string $root): array
    {
        return collect(config('scan.paths'))
            ->map(fn ($path) => $root.DIRECTORY_SEPARATOR.$path)
            ->filter(fn ($dir) => is_dir($dir))
            ->flatMap(fn ($dir) => File::allFiles($dir))
            ->filter(fn (SplFileInfo $file) => in_array(strtolower($file->getExtension()), config('scan.extensions'), true)
                && $file->getSize() <= config('scan.max_bytes'))
            ->map(fn (SplFileInfo $file) => $file->getPathname())
            ->sort()
            ->values()
            ->all();
    }

    // [line number, rule name] for every line that looks like a hard-coded secret.
    private function secrets(string $path): array
    {
        $hits = [];
        foreach (file($path) ?: [] as $index => $line) {
            foreach (config('scan.rules') as $rule => $pattern) {
                if (preg_match($pattern, $line)) {
                    $hits[] = [$index + 1, $rule];
                }
            }
        }

        return $hits;
    }

    // Compare with this user's previous finished scan: files that are new, edited or gone.
    private function changedSincePrevious(ScanRun $run, array $hashes): array
    {
        $previous = ScanRun::where('user_id', $run->user_id)
            ->where('status', 'done')
            ->where('id', '<', $run->id)
            ->latest('id')
            ->first();
        if (! $previous) {
            return [];
        }

        $old = $previous->hashes ?? [];
        $changed = [];
        foreach ($hashes as $file => $hash) {
            if (! isset($old[$file])) {
                $changed[] = ['file' => $file, 'change' => 'baru'];
            } elseif ($old[$file] !== $hash) {
                $changed[] = ['file' => $file, 'change' => 'berubah'];
            }
        }
        foreach (array_diff_key($old, $hashes) as $file => $hash) {
            $changed[] = ['file' => $file, 'change' => 'dihapus'];
        }

        return $changed;
    }

    // "app/Models/User.php" with forward slashes on every OS
    private function relative(string $root, string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen($root) + 1));
    }
}
