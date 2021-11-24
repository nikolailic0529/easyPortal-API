<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait OwnedByReseller {
    use OwnedByOrganization;

    public function getOrganizationColumn(): string {
        return 'reseller_id';
    }
}
