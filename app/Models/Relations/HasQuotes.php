<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Models\Model
 */
trait HasQuotes {
    public function quotes(): HasMany {
        return $this
            ->hasMany(Document::class)
            ->where(static function (Builder $builder): Builder {
                /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                return $builder->queryQuotes();
            });
    }
}
