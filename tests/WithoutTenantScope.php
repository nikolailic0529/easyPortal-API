<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Tenant\Eloquent\OwnedByTenantScope;

/**
 * @mixin \Tests\TestCase
 */
trait WithoutTenantScope {
    use GlobalScopes;

    private bool $withoutTenantScope;

    public function setUpWithoutTenantScope(): void {
        $this->withoutTenantScope = $this->setGlobalScopeDisabled(OwnedByTenantScope::class, true);
    }

    public function tearDownWithoutTenantScope(): void {
        $this->setGlobalScopeDisabled(OwnedByTenantScope::class, $this->withoutTenantScope);
    }
}
