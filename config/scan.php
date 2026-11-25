<?php

// Background workspace scan (Pertemuan 12, Challenge 3: wire:poll progress).
// The scanned folders are fixed here; the user can only press "start", never pass a path or a shell command,
// so the feature cannot be abused for path traversal or command injection.
return [

    // Folder the paths below are relative to
    'root' => base_path(),

    'paths' => ['app', 'config', 'routes', 'database', 'resources/views'],

    'extensions' => ['php', 'js', 'css', 'json', 'md', 'yml', 'yaml', 'xml'],

    // Bigger files are skipped (generated or binary, not hand-written code)
    'max_bytes' => 512 * 1024,

    // Pause after each file so the progress bar is visible in a demo; 0 = full speed
    'delay_ms' => (int) env('SCAN_DELAY_MS', 120),

    // Possible hard-coded credentials, checked line by line. Values read from env() do not match.
    'rules' => [
        'private_key_block' => '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
        'sk_prefixed_key' => '/\bsk-[A-Za-z0-9_-]{20,}/',
        'hardcoded_credential' => '/(?:api[_-]?key|secret|password|passwd|token)[\'"]?\s*(?:=>|=|:)\s*[\'"][^\'"\s]{8,}[\'"]/i',
    ],
];
