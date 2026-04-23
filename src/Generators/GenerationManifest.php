<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

class GenerationManifest
{
    private bool $bypassed = false;

    public function bypass(): void
    {
        $this->bypassed = true;
    }

    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    public function load(string $model): array
    {
        $path = $this->path($model);

        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        return array_values(array_filter(
            $lines,
            fn(string $line) => !str_starts_with(trim($line), '#')
        ));
    }

    public function merge(string $model, array $newArtifacts): void
    {
        $existing = $this->load($model);
        $merged   = array_values(array_unique(array_merge($existing, $newArtifacts)));

        $this->write($model, $merged);
    }

    public function isAlreadyGenerated(string $model, string $artifact): bool
    {
        if ($this->bypassed) {
            return false;
        }

        return in_array($artifact, $this->load($model), true);
    }

    public function diff(string $model, array $planned): array
    {
        $existing = $this->load($model);

        return [
            'new'      => array_values(array_diff($planned, $existing)),
            'existing' => array_values(array_intersect($planned, $existing)),
        ];
    }

    protected function write(string $model, array $artifacts): void
    {
        $dir = $this->dir();

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $header = implode("\n", [
            "# Generated artifacts for: {$model}",
            '# Last updated: ' . date('Y-m-d H:i:s'),
            '# Delete a line to re-generate that artifact on the next crud:sync run.',
            '',
        ]);

        file_put_contents($this->path($model), $header . implode("\n", $artifacts) . "\n");
    }

    protected function dir(): string
    {
        return base_path('.LaravelAttributeCodeGenerator');
    }

    private function path(string $model): string
    {
        return $this->dir() . "/{$model}.txt";
    }
}
