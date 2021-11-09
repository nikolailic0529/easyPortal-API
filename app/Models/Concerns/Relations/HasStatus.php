<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Status;

/**
 * @mixin \App\Models\Model
 */
trait HasStatus {
    use HasStatusNullable {
        setStatusAttribute as private setStatusAttributeNullable;
    }

    public function setStatusAttribute(Status $status): void {
        $this->setStatusAttributeNullable($status);
    }
}
