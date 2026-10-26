<?php

namespace Routegroup\Imoje\Payment\Tests;

function phpBlocks(): array
{
    $blocks = [];

    foreach (glob(__DIR__.'/../docs/*.md') ?: [] as $path) {
        $markdown = (string) file_get_contents($path);

        preg_match_all('/```php\n(.*?)```/s', $markdown, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $match) {
            [$code, $offset] = $match;
            $line = substr_count(substr($markdown, 0, $offset), "\n") + 1;
            $blocks[basename($path).':'.$line] = [$code];
        }
    }

    return $blocks;
}

it('parses every PHP example in the docs', function (string $code): void {
    $imports = [];
    $body = [];

    foreach (explode("\n", $code) as $line) {
        str_starts_with($line, 'use ') ? $imports[] = $line : $body[] = $line;
    }

    $body = implode("\n", $body);

    // A block declaring a method needs a class around it; anything else is a
    // sequence of statements and goes in a function body.
    $shell = preg_match('/\bfunction\s+\w+\s*\(/', $body) === 1
        ? "class DocExample {\n$body\n}"
        : "function docExample() {\n$body\n}";

    $file = tempnam(sys_get_temp_dir(), 'doc').'.php';
    file_put_contents($file, "<?php\n".implode("\n", $imports)."\n$shell\n");

    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $output, $status);
    unlink($file);

    expect($status)->toBe(0, implode("\n", $output));
})->with(phpBlocks());
