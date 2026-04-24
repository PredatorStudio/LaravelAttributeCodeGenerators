<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class MigrationGenerator
{
    public function generate(string $modelClass, bool $softDeletes = false): void
    {
        $model = new $modelClass;

        if (!method_exists($model, 'fields')) {
            throw new \RuntimeException("Model {$modelClass} must implement a fields() method.");
        }

        $tableName = Str::snake(Str::pluralStudly(class_basename($modelClass)));

        $schema = $this->buildSchema($model->fields());

        $migration = $this->wrapMigration($tableName, $schema, $softDeletes);

        $fileName = date('Y_m_d_His') . "_create_{$tableName}_table.php";

        File::put(database_path("migrations/{$fileName}"), $migration);
    }

    private function buildSchema(array $fields): string
    {
        $code = '';

        foreach ($fields as $field) {
            $type = $field['type'];
            $name = $field['name'] ?? null;

            switch ($type) {
                case 'id':
                    $code .= "            \$table->id();\n";
                    break;

                case 'primary':
                    $code .= "            \$table->primary(" . $this->arrayExport($field['columns']) . ");\n";
                    break;

                case 'foreignId':
                    if ($name === null) {
                        break;
                    }
                    $code .= $this->foreignColumn($field);
                    break;

                case 'string':
                case 'text':
                case 'integer':
                case 'boolean':
                case 'json':
                    if ($name === null) {
                        break;
                    }
                    $code .= $this->basicColumn($type, $name, $field);
                    break;
            }

            if (!empty($field['index']) && $type !== 'id' && $name !== null) {
                $indexName = $field['index_name'] ?? "{$name}_index";
                $code .= "            \$table->index('$name', '$indexName');\n";
            }
        }

        return $code;
    }

    private function basicColumn(string $type, string $name, array $field): string
    {
        $line = "            \$table->{$type}('$name')";

        if (!empty($field['nullable'])) {
            $line .= "->nullable()";
        }

        if (array_key_exists('default', $field)) {
            $default = var_export($field['default'], true);
            $line .= "->default($default)";
        }

        if (!empty($field['unique'])) {
            $line .= "->unique()";
        }

        return $line . ";\n";
    }

    private function foreignColumn(array $field): string
    {
        $name = $field['name'];
        $line = "            \$table->foreignId('$name')";

        // nullable must come before constrained()
        if (!empty($field['nullable'])) {
            $line .= "->nullable()";
        }

        if (!empty($field['references'])) {
            [$table, $column] = explode('.', $field['references']);
            $line .= "->constrained('$table', '$column')";
        } else {
            $line .= "->constrained()";
        }

        if (!empty($field['onDelete'])) {
            $line .= "->onDelete('{$field['onDelete']}')";
        }

        return $line . ";\n";
    }

    public function generateAlter(string $modelClass, array $newFields): void
    {
        $tableName = Str::snake(Str::pluralStudly(class_basename($modelClass)));

        $schema = $this->buildSchema($newFields);

        $migration = $this->wrapAlterMigration($tableName, $schema);

        $fileName = date('Y_m_d_His') . "_add_columns_to_{$tableName}_table.php";

        File::put(database_path("migrations/{$fileName}"), $migration);
    }

    private function wrapAlterMigration(string $table, string $schema): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('$table', function (Blueprint \$table) {
{$schema}        });
    }

    public function down(): void
    {
        Schema::table('$table', function (Blueprint \$table) {
            // TODO: drop added columns
        });
    }
};
PHP;
    }

    private function wrapMigration(string $table, string $schema, bool $softDeletes = false): string
    {
        $extra = $softDeletes
            ? "            \$table->timestamps();\n            \$table->softDeletes();"
            : "            \$table->timestamps();";

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('$table', function (Blueprint \$table) {
{$schema}{$extra}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('$table');
    }
};
PHP;
    }

    private function arrayExport(array $arr): string
    {
        return '[' . implode(', ', array_map(fn($v) => "'$v'", $arr)) . ']';
    }
}
