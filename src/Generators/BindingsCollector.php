<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Facades\File;

class BindingsCollector
{
    private const MARKER_START = '// @crud-generator:start';
    private const MARKER_END   = '// @crud-generator:end';

    private array $bindings = [];

    public function add(string $abstract, string $concrete): void
    {
        $this->bindings[] = compact('abstract', 'concrete');
    }

    public function isEmpty(): bool
    {
        return empty($this->bindings);
    }

    public function has(string $abstract): bool
    {
        foreach ($this->bindings as $b) {
            if ($b['abstract'] === $abstract) {
                return true;
            }
        }
        return false;
    }

    public function flush(): void
    {
        if (empty($this->bindings)) {
            return;
        }

        $dir = app_path('Providers');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $path    = app_path('Providers/GeneratedBindingsProvider.php');
        $content = File::exists($path) ? File::get($path) : null;

        File::put($path, $this->buildProvider($content));
    }

    private function buildProvider(?string $existing): string
    {
        $generatedBlock = $this->buildGeneratedBlock();

        if ($existing === null) {
            return $this->buildFullProvider($generatedBlock, '');
        }

        // Replace the generated section between the markers, keep the rest.
        $startMarker = self::MARKER_START;
        $endMarker   = self::MARKER_END;

        if (str_contains($existing, $startMarker) && str_contains($existing, $endMarker)) {
            return preg_replace(
                '/' . preg_quote($startMarker, '/') . '.*?' . preg_quote($endMarker, '/') . '/s',
                $generatedBlock,
                $existing
            );
        }

        // Provider exists but has no markers yet — inject the generated block inside register().
        if (preg_match('/public function register\(\): void\s*\{/', $existing)) {
            return preg_replace(
                '/(public function register\(\): void\s*\{)/',
                "$1\n        {$generatedBlock}",
                $existing
            );
        }

        // Fallback: rewrite completely (shouldn't happen in practice).
        $manualPart = $this->extractManualBindings($existing);
        return $this->buildFullProvider($generatedBlock, $manualPart);
    }

    private function buildGeneratedBlock(): string
    {
        $lines = self::MARKER_START . "\n";
        foreach ($this->bindings as $b) {
            $lines .= "        \$this->app->bind(\\{$b['abstract']}::class, \\{$b['concrete']}::class);\n";
        }
        $lines .= '        ' . self::MARKER_END;
        return $lines;
    }

    private function buildFullProvider(string $generatedBlock, string $manualBlock): string
    {
        $extra = $manualBlock ? "\n{$manualBlock}" : '';

        return <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GeneratedBindingsProvider extends ServiceProvider
{
    public function register(): void
    {
        {$generatedBlock}{$extra}
    }
}
PHP;
    }

    private function extractManualBindings(string $content): string
    {
        // Extract lines inside register() that don't look auto-generated.
        if (!preg_match('/public function register\(\): void\s*\{(.+?)}/s', $content, $m)) {
            return '';
        }
        $body = trim($m[1]);
        // Filter out lines that are between markers or are the markers themselves.
        $lines  = explode("\n", $body);
        $inside = false;
        $manual = [];

        foreach ($lines as $line) {
            if (str_contains($line, self::MARKER_START)) { $inside = true;  continue; }
            if (str_contains($line, self::MARKER_END))   { $inside = false; continue; }
            if (!$inside) {
                $manual[] = $line;
            }
        }

        $result = implode("\n", $manual);
        return trim($result) ? "\n        " . trim($result) . "\n" : '';
    }
}