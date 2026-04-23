<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

class RouteCollector
{
    protected array $routes = [];

    public function add(string $route, string $controller, array $methods): void
    {
        $this->routes[] = compact('route', 'controller', 'methods');
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
            if (empty($r['methods'])) {
                $output .= "Route::apiResource('{$r['route']}', {$r['controller']}::class);\n\n";
            } else {
                $methods = "['" . implode("','", $r['methods']) . "']";
                $output .= "Route::apiResource('{$r['route']}', {$r['controller']}::class)\n    ->only({$methods});\n\n";
            }
        }

        return $output;
    }
}
