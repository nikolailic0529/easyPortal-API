<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Status;
use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasStatus {
    use HasStatusNullable {
        setStatusAttribute as private setStatusAttributeNullable;
    }

    public function setStatusAttribute(Status $status): void {
        $this->setStatusAttributeNullable($status);
    }
}
