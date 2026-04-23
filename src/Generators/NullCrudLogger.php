<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

class NullCrudLogger implements CrudLogger
{
    public function line(string $message): void {}
    public function info(string $message): void {}
    public function success(string $message): void {}
    public function warn(string $message): void {}
}
