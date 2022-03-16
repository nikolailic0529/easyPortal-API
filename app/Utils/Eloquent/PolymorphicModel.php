<?php declare(strict_types = 1);

namespace App\Utils\Eloquent;

use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $object_type
 * @property string $object_id
 * @property Model  $object
 */
abstract class PolymorphicModel extends Model {
    #[CascadeDelete(false)]
    public function object(): MorphTo {
        return $this->morphTo('object');
    }

    public function setObjectAttribute(Model $object): void {
        $this->object_id   = $object->getKey();
        $this->object_type = $object->getMorphClass();
    }
}
