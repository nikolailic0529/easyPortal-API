<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Type;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasType {
    use HasTypeNullable {
        setTypeAttribute as private setTypeAttributeNullable;
    }

    public function setTypeAttribute(Type $type): void {
        $this->setTypeAttributeNullable($type);
    }
}
