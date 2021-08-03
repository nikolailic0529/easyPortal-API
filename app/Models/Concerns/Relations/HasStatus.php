<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Status;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

/**
 * @mixin \App\Models\Model
 */
trait HasStatus {
    public function status(): BelongsTo {
        return $this
            ->belongsTo(Status::class)
            ->where('object_type', '=', $this->getMorphClass());
    }

    public function setStatusAttribute(Status $status): void {
        if ($status->object_type !== $this->getMorphClass()) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $this->status()->associate($status);
    }
}
