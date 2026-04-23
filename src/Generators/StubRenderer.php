<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

class StubRenderer
{
    public function render(string $stubPath, array $vars): string
    {
        if (!file_exists($stubPath)) {
            throw new \RuntimeException("Stub not found: {$stubPath}");
        }

        $content = file_get_contents($stubPath);

        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}
