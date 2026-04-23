<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use ReflectionClass;
use ReflectionMethod;

class RelationDetector
{
    public function detect(string $modelClass): array
    {
        $reflection = new ReflectionClass($modelClass);

        $relations = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $modelClass) {
                continue;
            }

            $relation = $this->parseRelation($method);

            if ($relation) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    private function parseRelation(ReflectionMethod $method): ?array
    {
        $file = file_get_contents($method->getFileName());

        $methodName = $method->getName();

        $methodBody = $this->extractMethodBody($file, $methodName);

        if (str_contains($methodBody, 'belongsTo')) {
            return [
                'type' => 'belongsTo',
                'name' => $methodName,
                'model' => $this->extractModel($methodBody),
            ];
        }

        if (str_contains($methodBody, 'hasMany')) {
            return [
                'type' => 'hasMany',
                'name' => $methodName,
                'model' => $this->extractModel($methodBody),
            ];
        }

        return null;
    }

    private function extractModel(string $body): ?string
    {
        preg_match('/([A-Z][a-zA-Z0-9_\\\\]+)::class/', $body, $m);

        return $m[1] ?? null;
    }

    private function extractMethodBody(string $file, string $method): string
    {
        preg_match(
            "/function {$method}\(.*?\)\s*\{(.*?)\}/s",
            $file,
            $matches
        );

        return $matches[1] ?? '';
    }
}
