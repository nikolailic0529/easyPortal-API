<?php declare(strict_types = 1);

namespace Tests;

use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;

/**
 * @mixin TestCase
 */
trait WithoutOrganizationScope {
    private bool $withoutOrganizationScope;

    /**
     * @before
     */
    public function initWithoutOrganizationScope(): void {
        $this->afterApplicationCreated(function (): void {
            $this->withoutOrganizationScope = GlobalScopes::setDisabled(
                OwnedByOrganizationScope::class,
                true,
            );
        });

        $this->beforeApplicationDestroyed(function (): void {
            GlobalScopes::setDisabled(
                OwnedByOrganizationScope::class,
                $this->withoutOrganizationScope,
            );
        });
    }
}
