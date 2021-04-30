<?php declare(strict_types = 1);

namespace App\Models\Concerns\Tenants;

use App\Services\Tenant\Tenant;
use Illuminate\Database\Eloquent\Relations\Relation;

use function app;

/**
 * @mixin \App\Models\Model
 */
trait OwnedByTenant {
    public static function bootOwnedByTenant(): void {
        static::addGlobalScope(new OwnedByTenantScope(app()->make(Tenant::class)));
    }

    public function getQualifiedTenantColumn(): string {
        return $this->qualifyColumn('reseller_id');
    }

    public function getTenantThrough(): ?Relation {
        return null;
    }
}
