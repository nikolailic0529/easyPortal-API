<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Reseller;

/**
 * @mixin \App\Models\Model
 */
trait HasReseller {
    use HasResellerNullable {
        setResellerAttribute as private setResellerAttributeNullable;
    }

    public function setResellerAttribute(Reseller $reseller): void {
        $this->setResellerAttributeNullable($reseller);
    }
}
