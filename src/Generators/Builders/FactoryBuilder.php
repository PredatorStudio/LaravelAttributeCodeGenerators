<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

use ReflectionClass;

class FactoryBuilder
{
    public function build(string $modelClass): string
    {
        $model = (new ReflectionClass($modelClass))->getShortName();
        $instance = new $modelClass;
        $fields = method_exists($instance, 'fields') ? $instance->fields() : [];

        $lines = '';
        foreach ($fields as $field) {
            if (in_array($field['type'], ['id'])) {
                continue;
            }
            $faker = $this->fakerFor($field);
            $lines .= "            '{$field['name']}' => {$faker},\n";
        }

        return <<<PHP
<?php

namespace Database\Factories;

use App\Models\\{$model};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    public function definition(): array
    {
        return [
{$lines}        ];
    }
}
PHP;
    }

    private function fakerFor(array $field): string
    {
        $name = $field['name'] ?? '';

        if (str_contains($name, 'email')) {
            return 'fake()->safeEmail()';
        }

        if (str_contains($name, 'name')) {
            return 'fake()->name()';
        }

        return match ($field['type']) {
            'string'                              => 'fake()->word()',
            'text'                                => 'fake()->paragraph()',
            'integer', 'bigInteger', 'unsignedBigInteger' => 'fake()->randomNumber()',
            'boolean'                             => 'fake()->boolean()',
            'date'                                => 'fake()->date()',
            'dateTime', 'timestamp'               => 'fake()->dateTime()',
            'decimal', 'float', 'double'          => 'fake()->randomFloat(2)',
            default                               => 'fake()->word()',
        };
    }
}
