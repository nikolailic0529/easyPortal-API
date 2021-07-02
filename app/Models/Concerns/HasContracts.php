<?php declare(strict_types = 1);

namespace App\Models\Concerns;

use App\GraphQL\Queries\ContractTypes;
use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function app;

/**
 * @mixin \App\Models\Model
 */
trait HasContracts {
    public function contracts(): HasMany {
        return $this
            ->hasMany(Document::class)
            ->where(static function (Builder $builder) {
                return app()->make(ContractTypes::class)->prepare($builder);
            });
    }
}
