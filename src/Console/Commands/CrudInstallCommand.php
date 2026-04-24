<?php

namespace Vendor\LaravelAttributeCodeGenerators\Console\Commands;

use Illuminate\Console\Command;

class CrudInstallCommand extends Command
{
    protected $signature   = 'crud:install {--ai : Install Claude Code slash command for attribute descriptions}';
    protected $description = 'Install optional extras for the CRUD generator';

    public function handle(): int
    {
        if (!$this->option('ai')) {
            $this->line('Available options:');
            $this->line('  --ai    Install the /describe-attributes Claude Code slash command');
            return self::SUCCESS;
        }

        return $this->installAiSkill();
    }

    private function installAiSkill(): int
    {
        $source      = __DIR__ . '/../../../resources/ai/describe-attributes.md';
        $commandsDir = base_path('.claude/commands');
        $target      = $commandsDir . '/describe-attributes.md';

        if (!is_dir($commandsDir) && !mkdir($commandsDir, 0755, true)) {
            $this->error("Could not create directory: {$commandsDir}");
            return self::FAILURE;
        }

        if (file_exists($target) && !$this->confirm('describe-attributes.md already exists. Overwrite?')) {
            $this->line('Skipped.');
            return self::SUCCESS;
        }

        if (!copy($source, $target)) {
            $this->error("Failed to copy skill file to {$target}");
            return self::FAILURE;
        }

        $this->info('Installed: .claude/commands/describe-attributes.md');
        $this->line('Restart Claude Code, then use <comment>/describe-attributes</comment> to explore all attributes.');

        return self::SUCCESS;
    }
}
