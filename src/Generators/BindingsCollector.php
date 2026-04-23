<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Facades\File;

class BindingsCollector
{
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

        $bindingsCode = '';
        foreach ($this->bindings as $b) {
            $bindingsCode .= "        \$this->app->bind(\\{$b['abstract']}::class, \\{$b['concrete']}::class);\n";
        }

        $dir = app_path('Providers');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        file_put_contents(app_path('Providers/GeneratedBindingsProvider.php'), $this->buildProvider($bindingsCode));
    }

    private function buildProvider(string $bindings): string
    {
        return <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class GeneratedBindingsProvider extends ServiceProvider
{
    public function register(): void
    {
{$bindings}    }
}
PHP;
    }
}
