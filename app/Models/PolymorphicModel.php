<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string            $object_type
 * @property string            $object_id
 * @property \App\Models\Model $object
 */
abstract class PolymorphicModel extends Model {
    public function object(): MorphTo {
        return $this->morphTo('object');
    }

    public function setObjectAttribute(Model $object): void {
        $this->object_id   = $object->getKey();
        $this->object_type = $object->getMorphClass();
    }
}
