<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use ReflectionClass;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Action;
use Vendor\LaravelAttributeCodeGenerators\Attributes\BackedEnum;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Crud;
use Vendor\LaravelAttributeCodeGenerators\Attributes\DTO;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Factory;
use Vendor\LaravelAttributeCodeGenerators\Attributes\GenerateMigration;
use Vendor\LaravelAttributeCodeGenerators\Attributes\GenerateTest;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Observer;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Policy;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Repository;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Resource;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Route;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Seeder;
use Vendor\LaravelAttributeCodeGenerators\Attributes\Service;
use Vendor\LaravelAttributeCodeGenerators\Attributes\SoftDeletes;
use Vendor\LaravelAttributeCodeGenerators\Attributes\ValidateFromMigration;

class AttributeReader
{
    public function read(string $modelClass): array
    {
        $reflection = new ReflectionClass($modelClass);

        return [
            'class'               => $reflection,
            'shortName'           => $reflection->getShortName(),
            'crud'                => $this->getAttribute($reflection, Crud::class),
            'route'               => $this->getAttribute($reflection, Route::class),
            'service'             => $this->getAttribute($reflection, Service::class),
            'repository'         => $this->getAttribute($reflection, Repository::class),
            'policy'              => $this->hasAttribute($reflection, Policy::class),
            'validateFromMigration' => $this->hasAttribute($reflection, ValidateFromMigration::class),
            'dto'                 => $this->hasAttribute($reflection, DTO::class),
            'generateMigration'   => $this->hasAttribute($reflection, GenerateMigration::class),
            'resource'            => $this->getAttribute($reflection, Resource::class),
            'softDeletes'         => $this->hasAttribute($reflection, SoftDeletes::class),
            'backedEnums'         => $this->getAttributeInstances($reflection, BackedEnum::class),
            'observer'            => $this->hasAttribute($reflection, Observer::class),
            'action'              => $this->hasAttribute($reflection, Action::class),
            'factory'             => $this->hasAttribute($reflection, Factory::class),
            'seeder'              => $this->getAttribute($reflection, Seeder::class),
            'generateTest'        => $this->hasAttribute($reflection, GenerateTest::class),
        ];
    }

    private function getAttribute(ReflectionClass $class, string $attr): mixed
    {
        $attrs = $class->getAttributes($attr);
        return $attrs ? $attrs[0]->newInstance() : null;
    }

    private function hasAttribute(ReflectionClass $class, string $attr): bool
    {
        return !empty($class->getAttributes($attr));
    }

    private function getAttributeInstances(ReflectionClass $class, string $attr): array
    {
        return array_map(fn($a) => $a->newInstance(), $class->getAttributes($attr));
    }
}
