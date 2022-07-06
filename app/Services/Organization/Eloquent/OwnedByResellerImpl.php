<?php declare(strict_types = 1);

namespace App\Services\Organization\Eloquent;

use App\Utils\Eloquent\Model;

/**
 * @see OwnedByReseller
 *
 * @mixin Model
 */
trait OwnedByResellerImpl {
    use OwnedByImpl;

    public static function getOwnedByResellerColumn(): string {
        return 'reseller_id';
    }
}
