<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Document;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin Model
 */
trait HasContracts {
    use HasDocuments;

    #[CascadeDelete(false)]
    public function contracts(): HasMany {
        return $this
            ->documents()
            ->where(static function (Builder $builder) {
                /** @var Builder|Document $builder */
                return $builder->queryContracts();
            });
    }
}
