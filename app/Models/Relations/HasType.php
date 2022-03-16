<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Type;
use App\Utils\Eloquent\Model;

/**
 * @mixin Model
 */
trait HasType {
    use HasTypeNullable {
        setTypeAttribute as private setTypeAttributeNullable;
    }

    public function setTypeAttribute(Type $type): void {
        $this->setTypeAttributeNullable($type);
    }
}
