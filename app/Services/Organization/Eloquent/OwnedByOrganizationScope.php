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
        $relation = Relation::noConstraints(static function () use ($model): ?Relation {
            return $model->getOrganizationThrough();
        });

        if ($relation) {
            $builder->whereHas($relation, function (Builder $builder) use ($model): void {
                $builder->where($model->getQualifiedOrganizationColumn(), '=', $this->organization->getKey());
            });
        } else {
            $builder->where($model->getQualifiedOrganizationColumn(), '=', $this->organization->getKey());
        }
    }
}
