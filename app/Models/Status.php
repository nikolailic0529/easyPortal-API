<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\StatusFactory;
use Eloquent;
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
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property-read Collection<int, Asset>    $assets
 * @property-read Collection<int, Document> $contracts
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Document> $quotes
 * @method static StatusFactory factory(...$parameters)
 * @method static Builder|Status newModelQuery()
 * @method static Builder|Status newQuery()
 * @method static Builder|Status query()
 * @mixin Eloquent
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
                /** @var Builder|Document $builder */
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
                /** @var Builder|Document $builder */
                return $builder->queryQuotes();
            })
            ->withTimestamps();
    }
}
