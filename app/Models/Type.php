<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContracts;
use App\Models\Relations\HasQuotes;
use App\Models\Scopes\DocumentTypeQuery;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\PolymorphicModel;
use Carbon\CarbonImmutable;
use Database\Factories\TypeFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

/**
 * Type.
 *
 * @property string                         $id
 * @property string                         $object_type
 * @property string                         $key
 * @property string                         $name
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property-read Collection<int, Asset>    $assets
 * @property-read Collection<int, Contact>  $contacts
 * @property Collection<int, Document>      $contracts
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Document> $documents
 * @property-read Collection<int, Location> $locations
 * @property-read Collection<int, Document> $quotes
 * @method static TypeFactory factory(...$parameters)
 * @method static Builder|Type newModelQuery()
 * @method static Builder|Type newQuery()
 * @method static Builder|Type query()
 * @method static Builder|Type queryContracts()
 * @method static Builder|Type queryDocuments()
 * @method static Builder|Type queryQuotes()
 * @mixin Eloquent
 */
class Type extends PolymorphicModel implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;
    use HasContracts;
    use HasQuotes;
    use DocumentTypeQuery;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'types';

    protected function getTranslatableKey(): ?string {
        return "{$this->object_type}/{$this->key}";
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    #[CascadeDelete(false)]
    public function locations(): HasManyDeep {
        return $this->hasManyDeep(
            Location::class,
            [
                CustomerLocationType::class,
                CustomerLocation::class,
            ],
            [
                null,
                null,
                'id',
            ],
            [
                null,
                null,
                'location_id',
            ],
        );
    }

    #[CascadeDelete(true)]
    public function contacts(): BelongsToMany {
        $pivot = new ContactType();

        return $this
            ->belongsToMany(Contact::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    #[CascadeDelete(false)]
    public function customers(): HasMany {
        return $this->hasMany(Customer::class);
    }

    #[CascadeDelete(false)]
    public function documents(): HasMany {
        return $this
            ->hasMany(Document::class)
            ->where(static function (Builder $builder): Builder {
                /** @var Builder|Document $builder */
                return $builder->queryDocuments();
            });
    }
}
