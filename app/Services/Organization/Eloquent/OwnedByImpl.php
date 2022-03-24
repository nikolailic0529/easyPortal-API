<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Utils\Eloquent\Model;

use function app;

/**
 * @mixin Model
 */
trait OwnedByImpl {
    public static function bootOwnedByImpl(): void {
        static::addGlobalScope(app()->make(OwnedByOrganizationScope::class));
    }
}
