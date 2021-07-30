<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Models\Concerns\GlobalScopes\DisableableScope;
use App\Services\Organization\CurrentOrganization;
use App\Services\Search\Builder;
use App\Services\Search\ScopeWithMetadata;
use App\Utils\ModelProperty;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

class OwnedByOrganizationScope extends DisableableScope implements ScopeWithMetadata {
    protected const SEARCH_METADATA = 'organizations';

    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    // <editor-fold desc="Eloquent">
    // =========================================================================
    /**
     * @param \App\Models\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $model
     */
    protected function handle(EloquentBuilder $builder, Model $model): void {
        // Root organization can view all data
        if ($this->organization->isRoot()) {
            return;
        }

        // Hide data related to another organization
        $property     = new ModelProperty($model->getOrganizationColumn());
        $organization = $this->organization->getKey();

        if ($property->isRelation()) {
            $builder->whereHas(
                $property->getRelationName(),
                static function (EloquentBuilder $builder) use ($property, $organization): void {
                    $builder->where($builder->getModel()->qualifyColumn($property->getName()), '=', $organization);
                },
            );
        } else {
            $builder->where($model->qualifyColumn($property->getName()), '=', $organization);
        }
    }
    // </editor-fold>

    // <editor-fold desc="Search">
    // =========================================================================
    /**
     * @param \App\Models\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $model
     */
    protected function handleForSearch(Builder $builder, Model $model): void {
        // Root organization can view all data
        if ($this->organization->isRoot()) {
            return;
        }

        // Hide data related to another organization
        $builder->whereMetadata(static::SEARCH_METADATA, $this->organization->getKey());
    }

    /**
     * @param \App\Models\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $model
     *
     * @return array<string,string>
     */
    public function getSearchMetadata(Model $model): array {
        return [
            static::SEARCH_METADATA => $model->getOrganizationColumn(),
        ];
    }
    // </editor-fold>
}
