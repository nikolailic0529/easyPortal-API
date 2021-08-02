<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Models\Model
 */
trait HasTypeNullable {
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
