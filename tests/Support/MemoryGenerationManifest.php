<?php

namespace Tests\Support;

use Vendor\LaravelAttributeCodeGenerators\Generators\GenerationManifest;

class MemoryGenerationManifest extends GenerationManifest
{
    /** @var array<string, string[]> */
    private array $store = [];

    protected function write(string $model, array $artifacts): void
    {
        $this->store[$model] = $artifacts;
    }

    public function load(string $model): array
    {
        if ($this->isBypassed()) {
            return [];
        }

        return $this->store[$model] ?? [];
    }

    public function getStore(): array
    {
        return $this->store;
    }
}
