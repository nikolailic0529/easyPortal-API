<?php declare(strict_types = 1);

namespace App\Dev\IdeHelper;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand as LaravelIdeHelperModelsCommand;

class ModelsCommand extends LaravelIdeHelperModelsCommand {
    use GlobalScopes;

    public function getPropertiesFromMethods(mixed $model): void {
        // Required to fix "Organization is unknown" error.
        $this->callWithoutGlobalScope(OwnedByOrganizationScope::class, function () use ($model): void {
            parent::getPropertiesFromMethods($model);
        });
    }
}
