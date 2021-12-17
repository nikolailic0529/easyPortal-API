<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Services\Organization\CurrentOrganization;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Nuwave\Lighthouse\Execution\ModelsLoader\ModelsLoader;
use Nuwave\Lighthouse\Execution\ModelsLoader\SimpleModelsLoader;

class RelationLoader implements ModelsLoader {
    protected Loader       $propertyLoader;
    protected ModelsLoader $modelLoader;

    public function __construct(CurrentOrganization $organization, string $relation, string $property) {
        $this->propertyLoader = new Loader($organization, $property);
        $this->modelLoader    = new SimpleModelsLoader($relation, static function (): void {
            // empty
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Collection<\Illuminate\Database\Eloquent\Model> $parents
     */
    public function load(EloquentCollection $parents): void {
        $this->propertyLoader->load($parents);
        $this->modelLoader->load($parents);
    }

    public function extract(Model $model): mixed {
        return $this->modelLoader->extract($model);
    }
}
