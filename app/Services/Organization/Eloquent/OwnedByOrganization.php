<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use function app;

/**
 * @mixin \App\Models\Model
 */
trait OwnedByOrganization {
    public static function bootOwnedByOrganization(): void {
        static::addGlobalScope(app()->make(OwnedByOrganizationScope::class));
    }

    public function getOrganizationColumn(): string {
        return 'organization_id';
    }
}
