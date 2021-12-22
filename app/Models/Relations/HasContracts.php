<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasContracts {
    use HasDocuments;

    #[CascadeDelete(false)]
    public function contracts(): HasMany {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|\App\Models\Document $builder */
                return $builder->queryContracts();
            });
    }
}
