<?php declare(strict_types = 1);

namespace App\Services\Tenant\Eloquent;

use App\Models\Concerns\GlobalScopes\DisableableScope;
use App\Services\Tenant\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OwnedByTenantScope extends DisableableScope {
    public function __construct(
        protected Tenant $tenant,
    ) {
        // empty
    }

    /**
     * @param \App\Models\Model&\App\Services\Tenant\Eloquent\OwnedByTenant $model
     */
    protected function handle(Builder $builder, Model $model): void {
        // Root organization can view all data
        if ($this->tenant->isRoot()) {
            return;
        }

        // Hide data related to another tenant
        $relation = $model->getTenantThrough();

        if ($relation) {
            $builder->whereHas($relation, function (Builder $builder) use ($model): void {
                $builder->where($model->getQualifiedTenantColumn(), '=', $this->tenant->getKey());
            });
        } else {
            $builder->where($model->getQualifiedTenantColumn(), '=', $this->tenant->getKey());
        }
    }
}
