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

        // --- Phase 1: Scan & plan ---

        $this->logger->info('Scanning models...');

        $models = $this->scanner->scan();
        $total = count($models);

        if ($total === 0) {
            $this->logger->warn('No models found. Nothing to generate.');
            return;
        }

        $plans = [];
        foreach ($models as $modelClass) {
            $plans[class_basename($modelClass)] = $this->processor->plan($modelClass);
        }

        $modelNames = implode(', ', array_keys($plans));
        $this->logger->info("Found {$total} " . ($total === 1 ? 'model' : 'models') . ": {$modelNames}");
        $this->logger->line('');

        // --- Phase 2: Show complete generation plan ---

        $planLabel = $dryRun ? 'Generation plan (dry run — no files will be written):' : 'Generation plan:';
        $this->logger->info($planLabel);
        foreach ($plans as $name => $files) {
            if (empty($files)) {
                $this->logger->warn("  {$name}  →  (skipped — no #[Crud] attribute)");
            } else {
                $diff    = $this->manifest->diff($name, $files);
                $newOnes = $diff['new'];
                $skip    = $diff['existing'];

                $parts = [];
                if (!empty($newOnes)) $parts[] = implode(', ', $newOnes);
                if (!empty($skip))    $parts[] = '(' . implode(', ', $skip) . ' — already generated)';

                $this->logger->line("  {$name}  →  " . implode('  ', $parts));
            }
        }
        $this->logger->line('');

        if ($dryRun) {
            $this->logger->warn('Dry run complete. No files were written.');
            return;
        }

        // --- Phase 3: Generate ---

        $summary = [];

        foreach ($models as $index => $modelClass) {
            $current = $index + 1;
            $name = class_basename($modelClass);
            $modelStart = microtime(true);

            $this->logger->info("[{$current}/{$total}] Generating {$name}...");

            $generated = $this->processor->process($modelClass, $this->routes, $this->bindings, $this->manifest);

            $elapsed = $this->elapsed($modelStart);
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

        // --- Phase 4: Flush bindings & routes ---

        if (!$this->bindings->isEmpty()) {
            $this->bindings->flush();
            $this->logger->line('Writing bindings → app/Providers/GeneratedBindingsProvider.php');
        }

        $this->routes->flush();
        $this->logger->line('Writing routes → routes/generated.php');
        $this->logger->line('');

        // --- Phase 5: Final summary ---

        $totalFiles = array_sum(array_map('count', $summary));
        $totalElapsed = $this->elapsed($totalStart);

        $this->logger->info(
            "Generation complete ({$total} " . ($total === 1 ? 'model' : 'models') .
            ", {$totalFiles} " . ($totalFiles === 1 ? 'file' : 'files') .
            ", {$totalElapsed}ms)"
        );
        $this->logger->line('');
        $this->logger->info('Summary:');

        foreach ($summary as $model => $files) {
            $pad = str_pad($model . ':', max(array_map(fn($n) => strlen($n) + 1, array_keys($summary))));
            $this->logger->line("  {$pad}  " . implode(', ', $files));
        }
    }

    private function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
