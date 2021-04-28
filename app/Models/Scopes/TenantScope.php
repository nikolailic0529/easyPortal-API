<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\Models\Contracts\BelongsToTenant;
use App\Services\Tenant\Tenant;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope {
    protected Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function apply(Builder $builder, Model $model) {
        if ($model instanceof BelongsToTenant) {
            $column = $model->getTenantIdColumn();
            $tenant = $this->container->make(Tenant::class);

            $builder->where($column, '=', $tenant->getKey());
        }
    }
}
