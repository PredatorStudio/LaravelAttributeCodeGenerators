<?php

namespace Vendor\LaravelAttributeCodeGenerators\Generators\Builders;

class ActionBuilder
{
    public function build(string $model): string
    {
        return <<<PHP
<?php

namespace App\Actions;

use App\Models\\{$model};

class Create{$model}Action
{
    public function execute(array \$data): {$model}
    {
        return {$model}::create(\$data);
    }
}
PHP;
    }

    public function buildUpdate(string $model): string
    {
        $var = lcfirst($model);

        return <<<PHP
<?php

namespace App\Actions;

use App\Models\\{$model};

class Update{$model}Action
{
    public function execute({$model} \${$var}, array \$data): {$model}
    {
        \${$var}->update(\$data);

        return \${$var};
    }
}
PHP;
    }

    public function buildDelete(string $model): string
    {
        $var = lcfirst($model);

        return <<<PHP
<?php

namespace App\Actions;

use App\Models\\{$model};

class Delete{$model}Action
{
    public function execute({$model} \${$var}): void
    {
        \${$var}->delete();
    }
}
PHP;
    }
}
