<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\CurrentTenant;
use App\Models\Contracts\BelongsToTenant as BelongsToTenantContract;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;

use function app;
use function is_null;

/**
 * @mixin \App\Models\Model
 */
trait BelongsToTenant {
    /**
     * @inheritdoc
     */
    protected function performInsert(Builder $query) {
        $column = $this->getTenantIdColumn();

        if ($this instanceof BelongsToTenantContract && is_null($this->{$column})) {
            $this->{$column} = app()->make(CurrentTenant::class)->getKey();
        }

        return parent::performInsert($query);
    }

    public function getTenantIdColumn(): string {
        return 'organization_id';
    }


    /**
     * @inheritdoc
     */
    protected static function booted() {
        parent::boot();
        static::addGlobalScope(new TenantScope(app()));
    }
}
