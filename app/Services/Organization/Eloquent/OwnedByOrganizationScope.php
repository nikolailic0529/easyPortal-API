<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Models\Concerns\GlobalScopes\DisableableScope;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        $org      = $this->organization->getKey();
        $column   = $model->getOrganizationColumn();
        $relation = Relation::noConstraints(static function () use ($model): ?Relation {
            return $model->getOrganizationThrough();
        });

        if ($relation instanceof Relation) {
            $builder->whereHas($relation, static function (Builder $builder) use ($column, $relation, $org): void {
                $builder->where($relation->qualifyColumn($column), '=', $org);
            });
        } else {
            $builder->where($model->qualifyColumn($column), '=', $org);
        }
    }
}
