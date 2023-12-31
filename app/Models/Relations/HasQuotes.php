<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin Model
 */
trait HasQuotes {
    use HasDocuments;

    /**
     * @return HasMany<Document>|HasManyThrough<Document>
     */
    public function quotes(): HasMany|HasManyThrough {
        return $this
            ->documents()
            ->where(static function (Builder $builder): Builder {
                /** @var Builder<Document> $builder */
                return $builder->queryQuotes();
            });
    }
}
