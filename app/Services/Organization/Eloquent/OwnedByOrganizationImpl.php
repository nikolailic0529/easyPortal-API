<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @mixin Model
 */
trait OwnedByOrganizationImpl {
    public static function bootOwnedByOrganizationImpl(): void {
        static::addGlobalScope(app()->make(OwnedByOrganizationScope::class));
    }

    public function getOrganizationColumn(): string {
        return 'organization_id';
    }
}
