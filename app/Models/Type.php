<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContracts;
use App\Models\Relations\HasQuotes;
use App\Models\Scopes\DocumentTypeQueries;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\PolymorphicModel;
use Carbon\CarbonImmutable;
use Database\Factories\TypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

/**
 * Type.
 *
 * @property string                                 $id
 * @property string                                 $object_type
 * @property string                                 $key
 * @property string                                 $name
 * @property CarbonImmutable                        $created_at
 * @property CarbonImmutable                        $updated_at
 * @property CarbonImmutable|null                   $deleted_at
 * @property-read Collection<int, Asset>            $assets
 * @property-read Collection<int, Contact>          $contacts
 * @property-read Collection<int, Document>         $contracts
 * @property-read Collection<int, Customer>         $customers
 * @property-read Collection<int, CustomerLocation> $customerLocations
 * @property-read Collection<int, Location>         $locations
 * @property-read Collection<int, Document>         $quotes
 * @method static TypeFactory factory(...$parameters)
 * @method static Builder|Type newModelQuery()
 * @method static Builder|Type newQuery()
 * @method static Builder|Type query()
 */
class Type extends PolymorphicModel implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;
    use HasContracts;
    use HasQuotes;

    /**
     * @use DocumentTypeQueries<static>
     */
    use DocumentTypeQueries;

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

    /**
     * @return BelongsToMany<CustomerLocation>
     */
    #[CascadeDelete(false)]
    public function customerLocations(): BelongsToMany {
        $pivot = new CustomerLocationType();

        return $this
            ->belongsToMany(CustomerLocation::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Contact>
     */
    #[CascadeDelete(false)]
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
}
