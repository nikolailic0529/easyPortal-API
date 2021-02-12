<?php declare(strict_types = 1);

namespace App\Models\Scopes;

use App\CurrentTenant;
use App\Models\Contracts\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope {
    protected CurrentTenant $tenant;

    public function __construct(CurrentTenant $tenant) {
        $this->tenant = $tenant;
    }

    /**
     * @inheritdoc
     */
    public function apply(Builder $builder, Model $model) {
        if ($model instanceof BelongsToTenant) {
            $builder->where($model->getTenantIdColumn(), '=', $this->tenant->get()->getKey());
        }
    }
}
