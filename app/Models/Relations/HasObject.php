<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $object_type
 * @property string $object_id
 * @property Model  $object
 *
 * @mixin Model
 */
trait HasObject {
    /**
     * @return MorphTo<EloquentModel, self>
     */
    public function object(): MorphTo {
        return $this->morphTo();
    }

    public function setObjectAttribute(Model $object): void {
        $this->object_id   = $object->getKey();
        $this->object_type = $object->getMorphClass();

        $this->setRelation('object', $object);
    }
}
