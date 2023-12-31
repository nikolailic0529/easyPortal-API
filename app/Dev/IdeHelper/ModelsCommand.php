<?php declare(strict_types = 1);

namespace App\Dev\IdeHelper;

use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand as LaravelIdeHelperModelsCommand;

class ModelsCommand extends LaravelIdeHelperModelsCommand {
    public function getPropertiesFromMethods(mixed $model): void {
        // Required to fix "Organization is unknown" error.
        GlobalScopes::callWithoutAll(function () use ($model): void {
            parent::getPropertiesFromMethods($model);
        });
    }
}
