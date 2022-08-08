<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TModel of \App\Utils\Eloquent\Model
 *
 * @property Collection<int, TModel> $fields
 *
 * @mixin Model
 */
trait HasFields {
    use SyncHasMany;

    /**
     * @return HasMany<TModel>
     */
    #[CascadeDelete(true)]
    public function fields(): HasMany {
        return $this->hasMany(
            $this->getFieldsModel(),
            $this->getFieldsForeignKey(),
        );
    }

    /**
     * @param Collection<int,TModel> $fields
     */
    public function setFieldsAttribute(Collection $fields): void {
        $this->syncHasMany('fields', $fields);
    }

    /**
     * @return class-string<TModel>
     */
    abstract protected function getFieldsModel(): string;

    protected function getFieldsForeignKey(): ?string {
        return null;
    }
}
