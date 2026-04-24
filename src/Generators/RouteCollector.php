<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

class RouteCollector
{
    protected array $routes = [];

    public function add(string $route, string $controller, array $methods, array $middleware = []): void
    {
        $this->routes[] = compact('route', 'controller', 'methods', 'middleware');
    }

    public function flush(): void
    {
        file_put_contents(
            base_path('routes/generated.php'),
            $this->buildOutput()
        );
    }

    public function buildOutput(): string
    {
        $output = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n";

        foreach ($this->routes as $r) {
            $line = "Route::apiResource('{$r['route']}', {$r['controller']}::class)";

            if (!empty($r['methods'])) {
                $methods = "['" . implode("','", $r['methods']) . "']";
                $line   .= "\n    ->only({$methods})";
            }

            if (!empty($r['middleware'])) {
                $mw   = "['" . implode("','", $r['middleware']) . "']";
                $line .= "\n    ->middleware({$mw})";
            }

            $output .= $line . ";\n\n";
        }

        return $output;
    }
}
