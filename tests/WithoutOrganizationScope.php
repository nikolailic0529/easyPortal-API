<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;

/**
 * @mixin \Tests\TestCase
 */
trait WithoutOrganizationScope {
    private bool $withoutOrganizationScope;

    public function setUpWithoutOrganizationScope(): void {
        $this->withoutOrganizationScope = GlobalScopes::setGlobalScopeDisabled(OwnedByOrganizationScope::class, true);
    }

    public function tearDownWithoutOrganizationScope(): void {
        GlobalScopes::setGlobalScopeDisabled(OwnedByOrganizationScope::class, $this->withoutOrganizationScope);
    }
}
