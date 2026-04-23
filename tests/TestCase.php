<?php

namespace Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Vendor\LaravelAttributeCodeGenerators\Providers\LaravelAttributeCodeGeneratorsProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelAttributeCodeGeneratorsProvider::class];
    }
}
