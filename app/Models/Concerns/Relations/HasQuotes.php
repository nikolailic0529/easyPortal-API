<?php declare(strict_types = 1);

namespace App\Models\Concerns\Relations;

use App\GraphQL\Queries\QuoteTypes;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function app;

/**
 * @mixin \App\Models\Model
 */
trait HasQuotes {
    public function quotes(): HasMany {
        return $this
            ->hasMany(Document::class)
            ->where(static function (Builder $builder): Builder {
                return app()->make(QuoteTypes::class)->prepare($builder);
            });
    }
}
