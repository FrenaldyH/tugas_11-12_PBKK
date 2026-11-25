<?php

namespace App\Support;

use Illuminate\Support\Str;

// Turns AI answers (Markdown) into safe HTML and pulls out their code blocks for the code panel.
class Markdown
{
    // Raw HTML written by the model is escaped and javascript: links are dropped, so an answer can never
    // inject a script into the page (the output is printed with {!! !!}).
    public static function toHtml(string $text): string
    {
        return Str::markdown($text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);
    }

    // Fenced blocks (```lang ... ```) in the order they appear: [['lang' => 'php', 'code' => '...'], ...].
    // The fence may be indented (models often put code inside a list item).
    public static function codeBlocks(string $text): array
    {
        preg_match_all('/^[ \t]*```[ \t]*([\w#+.-]*)[^\n]*\n(.*?)^[ \t]*```/ms', $text, $matches, PREG_SET_ORDER);

        return array_map(fn ($m) => [
            'lang' => strtolower($m[1]) ?: 'text',
            'code' => rtrim($m[2], "\n"),
        ], $matches);
    }

    // File extension shown on the code panel tab, e.g. "php" -> "blok-1.php".
    public static function extension(string $lang): string
    {
        return [
            'javascript' => 'js', 'typescript' => 'ts', 'python' => 'py', 'bash' => 'sh', 'shell' => 'sh',
            'sh' => 'sh', 'blade' => 'blade.php', 'markdown' => 'md', 'yaml' => 'yml', 'text' => 'txt',
            'plaintext' => 'txt', 'dotenv' => 'env', 'csharp' => 'cs', 'c++' => 'cpp', 'golang' => 'go',
        ][$lang] ?? $lang;
    }
}
