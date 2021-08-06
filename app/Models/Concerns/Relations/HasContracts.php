<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasContracts {
    public function contracts(): HasMany {
        return $this
            ->hasMany(Document::class)
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                return $builder->queryContracts();
            });
    }
}