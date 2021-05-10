<?php declare(strict_types = 1);

namespace Tests;

use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;

/**
 * @mixin \Tests\TestCase
 */
trait WithoutOrganizationScope {
    use GlobalScopes;

    private bool $withoutOrganizationScope;

    public function setUpWithoutOrganizationScope(): void {
        $this->withoutOrganizationScope = $this->setGlobalScopeDisabled(OwnedByOrganizationScope::class, true);
    }

    public function tearDownWithoutOrganizationScope(): void {
        $this->setGlobalScopeDisabled(OwnedByOrganizationScope::class, $this->withoutOrganizationScope);
    }
}
