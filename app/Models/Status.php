<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Status.
 *
 * @property string                         $id
 * @property string                         $object_type
 * @property string                         $key
 * @property string                         $name
 * @property \Carbon\CarbonImmutable        $created_at
 * @property \Carbon\CarbonImmutable        $updated_at
 * @property \Carbon\CarbonImmutable|null   $deleted_at
 * @property-read Collection<int, Asset>    $assets
 * @property-read Collection<int, Document> $contracts
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Document> $quotes
 * @method static \Database\Factories\StatusFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Status query()
 * @mixin \Eloquent
 */
class Status extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'statuses';

    protected function getTranslatableKey(): ?string {
        return "{$this->object_type}/{$this->key}";
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    #[CascadeDelete(true)]
    public function customers(): BelongsToMany {
        $pivot = new CustomerStatus();

        return $this
            ->belongsToMany(Customer::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    #[CascadeDelete(true)]
    public function contracts(): BelongsToMany {
        $pivot = new DocumentStatus();

        return $this
            ->belongsToMany(Document::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|Document $builder */
                return $builder->queryContracts();
            })
            ->withTimestamps();
    }

    #[CascadeDelete(true)]
    public function quotes(): BelongsToMany {
        $pivot = new DocumentStatus();

        return $this
            ->belongsToMany(Document::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|Document $builder */
                return $builder->queryQuotes();
            })
            ->withTimestamps();
    }

    #[CascadeDelete(true)]
    public function documents(): BelongsToMany {
        $pivot = new DocumentStatus();

        return $this
            ->belongsToMany(Document::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->where(static function (Builder $builder) {
                /** @var \Illuminate\Database\Eloquent\Builder|Document $builder */
                return $builder->queryDocuments();
            })
            ->withTimestamps();
    }
}
