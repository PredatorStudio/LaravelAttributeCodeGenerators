<?php

namespace Tests\Support;

use Vendor\LaravelAttributeCodeGenerators\Generators\ModelModifier;

class MemoryModelModifier extends ModelModifier
{
    public array $modifications = [];

    protected function writeFile(string $path, string $content): void
    {
        $this->modifications[$path] = $content;
    }

    public function wasModified(): bool
    {
        return !empty($this->modifications);
    }

    public function getContent(): ?string
    {
        return $this->modifications ? array_values($this->modifications)[0] : null;
    }
}
