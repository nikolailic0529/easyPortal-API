<?php declare(strict_types = 1);

namespace Tests;

use App\Utils\Eloquent\GlobalScopes\GlobalScopes;

/**
 * @mixin TestCase
 */
trait WithoutGlobalScopes {
    private bool $withoutGlobalScopes;

    /**
     * @before
     */
    public function initWithoutGlobalScopes(): void {
        $this->afterApplicationCreated(function (): void {
            $this->withoutGlobalScopes = GlobalScopes::setDisabledAll(true);
        });

        $this->beforeApplicationDestroyed(function (): void {
            GlobalScopes::setDisabledAll($this->withoutGlobalScopes);
        });
    }
}
