<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait OwnedByOrganizationImpl {
    use OwnedByImpl;

    public static function getOwnedByOrganizationColumn(): string {
        return 'organization_id';
    }
}
