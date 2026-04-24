<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

class CrudGenerator
{
    public function __construct(
        private ModelScanner $scanner,
        private ModelProcessor $processor,
        private RouteCollector $routes,
        private BindingsCollector $bindings,
        private CrudLogger $logger,
        private GenerationManifest $manifest,
    ) {}

    public function generateAll(bool $dryRun = false): void
    {
        $totalStart = microtime(true);

        $models = $this->scanModels();

        if (empty($models)) {
            $this->logger->warn('No models found. Nothing to generate.');
            return;
        }

        $plans = $this->buildPlans($models);
        $this->showPlan($plans, $dryRun);

        if ($dryRun) {
            $this->logger->warn('Dry run complete. No files were written.');
            return;
        }

        $summary = $this->runGeneration($models);
        $this->flushOutputs();
        $this->showSummary($summary, count($models), $this->elapsed($totalStart));
    }

    private function scanModels(): array
    {
        $this->logger->info('Scanning models...');
        return $this->scanner->scan();
    }

    private function buildPlans(array $models): array
    {
        $plans = [];
        foreach ($models as $modelClass) {
            $plans[class_basename($modelClass)] = $this->processor->plan($modelClass);
        }

        $total      = count($models);
        $modelNames = implode(', ', array_keys($plans));
        $this->logger->info("Found {$total} " . ($total === 1 ? 'model' : 'models') . ": {$modelNames}");
        $this->logger->line('');

        return $plans;
    }

    private function showPlan(array $plans, bool $dryRun): void
    {
        $label = $dryRun ? 'Generation plan (dry run — no files will be written):' : 'Generation plan:';
        $this->logger->info($label);

        foreach ($plans as $name => $files) {
            if (empty($files)) {
                $this->logger->warn("  {$name}  →  (skipped — no #[Crud] attribute)");
                continue;
            }

            $diff  = $this->manifest->diff($name, $files);
            $parts = [];

            if (!empty($diff['new']))      $parts[] = implode(', ', $diff['new']);
            if (!empty($diff['existing'])) $parts[] = '(' . implode(', ', $diff['existing']) . ' — already generated)';

            $this->logger->line("  {$name}  →  " . implode('  ', $parts));
        }

        $this->logger->line('');
    }

    private function runGeneration(array $models): array
    {
        $total   = count($models);
        $summary = [];

        foreach ($models as $index => $modelClass) {
            $current    = $index + 1;
            $name       = class_basename($modelClass);
            $modelStart = microtime(true);

            $this->logger->info("[{$current}/{$total}] Generating {$name}...");

            $generated = $this->processor->process($modelClass, $this->routes, $this->bindings, $this->manifest);
            $elapsed   = $this->elapsed($modelStart);
            $fileCount = count($generated);

            if (empty($generated)) {
                $this->logger->warn("[{$current}/{$total}] {$name} skipped");
            } else {
                $summary[$name] = $generated;
                $this->manifest->merge($name, $generated);
                $this->logger->success(
                    "[{$current}/{$total}] {$name} done ({$fileCount} " . ($fileCount === 1 ? 'file' : 'files') . ", {$elapsed}ms)"
                );
            }

            $this->logger->line('');
        }

        return $summary;
    }

    private function flushOutputs(): void
    {
        if (!$this->bindings->isEmpty()) {
            $this->bindings->flush();
            $this->logger->line('Writing bindings → app/Providers/GeneratedBindingsProvider.php');
        }

        $this->routes->flush();
        $this->logger->line('Writing routes → routes/generated.php');
        $this->logger->line('');
    }

    private function showSummary(array $summary, int $totalModels, int $elapsed): void
    {
        $totalFiles = array_sum(array_map('count', $summary));

        $this->logger->info(
            "Generation complete ({$totalModels} " . ($totalModels === 1 ? 'model' : 'models') .
            ", {$totalFiles} " . ($totalFiles === 1 ? 'file' : 'files') .
            ", {$elapsed}ms)"
        );
        $this->logger->line('');
        $this->logger->info('Summary:');

        $padWidth = max(array_map(fn($n) => strlen($n) + 1, array_keys($summary)));
        foreach ($summary as $model => $files) {
            $this->logger->line("  " . str_pad($model . ':', $padWidth) . "  " . implode(', ', $files));
        }
    }

    private function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
