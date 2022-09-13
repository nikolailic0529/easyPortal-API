<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Type;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin Model
 */
trait HasTypeNullable {
    /**
     * @return BelongsTo<Type, self>
     */
    public function type(): BelongsTo {
        return $this->belongsTo(Type::class);
    }

    public function setTypeAttribute(?Type $type): void {
        if ($type && $type->object_type !== $this->getMorphClass()) {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be `%s`, `%s` given.',
                $this->getMorphClass(),
                $type->object_type,
            ));
        }

        $this->type()->associate($type);
    }
}
