<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Field;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * @property Field $field
 *
 * @mixin Model
 */
trait HasField {
    /**
     * @return BelongsTo<Field, self>
     */
    #[CascadeDelete(false)]
    public function field(): BelongsTo {
        return $this->belongsTo(Field::class);
    }

    public function setFieldAttribute(Field $field): void {
        if ($field->object_type !== $this->getMorphClass()) {
            throw new InvalidArgumentException(sprintf(
                'The `$field` must be `%s`, `%s` given.',
                $this->getMorphClass(),
                $field->object_type,
            ));
        }

        $this->field()->associate($field);
    }
}
