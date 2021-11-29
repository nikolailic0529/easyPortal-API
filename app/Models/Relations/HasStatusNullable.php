<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Status;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasStatusNullable {
    public function status(): BelongsTo {
        return $this->belongsTo(Status::class);
    }

    public function setStatusAttribute(?Status $status): void {
        if ($status && $status->object_type !== $this->getMorphClass()) {
            throw new InvalidArgumentException(sprintf(
                'The `$status` must be `%s`, `%s` given.',
                $this->getMorphClass(),
                $status->object_type,
            ));
        }

        $this->status()->associate($status);
    }
}
