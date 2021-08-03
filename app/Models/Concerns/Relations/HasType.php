<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Models\Model
 */
trait HasType {
    use HasTypeNullable {
        setTypeAttribute as private setTypeAttributeNullable;
    }

    public function type(): BelongsTo {
        return $this->belongsTo(Type::class);
    }

    public function setTypeAttribute(Type $type): void {
        $this->setTypeAttributeNullable($type);
    }
}
