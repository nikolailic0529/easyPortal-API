<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait OwnedByResellerImpl {
    use OwnedByOrganizationImpl;

    public function getOrganizationColumn(): string {
        return 'reseller_id';
    }
}
