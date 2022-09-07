<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Oem;
use App\Utils\Eloquent\Model;

/**
 * @property Oem $oem
 *
 * @mixin Model
 */
trait HasOem {
    use HasOemNullable {
        setOemAttribute as private setOemAttributeNullable;
    }

    public function setOemAttribute(Oem $oem): void {
        $this->setOemAttributeNullable($oem);
    }
}
