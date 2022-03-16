<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Reseller;
use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasReseller {
    use HasResellerNullable {
        setResellerAttribute as private setResellerAttributeNullable;
    }

    public function setResellerAttribute(Reseller $reseller): void {
        $this->setResellerAttributeNullable($reseller);
    }
}
