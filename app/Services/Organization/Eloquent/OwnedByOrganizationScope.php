<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Models\Concerns\GlobalScopes\DisableableScope;
use App\Services\Organization\CurrentOrganization;
use App\Utils\ModelProperty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OwnedByOrganizationScope extends DisableableScope {
    public function __construct(
        protected CurrentOrganization $organization,
    ) {
        // empty
    }

    /**
     * @param \App\Models\Model&\App\Services\Organization\Eloquent\OwnedByOrganization $model
     */
    protected function handle(Builder $builder, Model $model): void {
        // Root organization can view all data
        if ($this->organization->isRoot()) {
            return;
        }

        // Hide data related to another organization
        $organization = $this->organization->getKey();
        $property     = new ModelProperty($model->getOrganizationColumn());

        if ($property->isRelation()) {
            $builder->whereHas(
                $property->getRelationName(),
                static function (Builder $builder) use ($property, $organization): void {
                    $builder->where($builder->getModel()->qualifyColumn($property->getName()), '=', $organization);
                },
            );
        } else {
            $builder->where($model->qualifyColumn($property->getName()), '=', $organization);
        }
    }
}
