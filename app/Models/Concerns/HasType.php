<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @mixin \App\Models\Model
 */
trait HasType {
    public function type(): BelongsTo {
        return $this
            ->belongsTo(Type::class)
            ->where('object_type', '=', $this->getMorphClass());
    }

    public function setTypeAttribute(Type $type): void {
        if ($type->object_type !== $this->getMorphClass()) {
            throw new InvalidArgumentException('Invalid type.');
        }

        $this->type()->associate($type);
    }
}
