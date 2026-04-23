<?php

namespace Vendor\LaravelAttributeCodeGenerators\Console;

use Illuminate\Console\Command;
use Vendor\LaravelAttributeCodeGenerators\Generators\CrudLogger;

class ConsoleCrudLogger implements CrudLogger
{
    public function __construct(private Command $command) {}

    public function line(string $message): void
    {
        $this->command->line($message);
    }

    public function info(string $message): void
    {
        $this->command->line("<fg=cyan>{$message}</fg=cyan>");
    }

    public function success(string $message): void
    {
        $this->command->line("<fg=green>{$message}</fg=green>");
    }

    public function warn(string $message): void
    {
        $this->command->line("<fg=yellow>{$message}</fg=yellow>");
    }
}
