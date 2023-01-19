<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Asset;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\CustomerLocation;
use App\Models\CustomerLocationType;
use App\Models\Document;
use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContracts;
use App\Models\Relations\HasQuotes;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\TypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
 * @property-read Collection<int, CustomerLocation> $customerLocations
 * @property-read Collection<int, Location>         $locations
 * @property-read Collection<int, Document>         $quotes
 * @method static TypeFactory factory(...$parameters)
 * @method static Builder<Type>|Type newModelQuery()
 * @method static Builder<Type>|Type newQuery()
 * @method static Builder<Type>|Type query()
 */
class Type extends Model implements DataModel, Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;
    use HasContracts;
    use HasQuotes;

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

    /**
     * @return BelongsToMany<CustomerLocation>
     */
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
    public function contacts(): BelongsToMany {
        $pivot = new ContactType();

        return $this
            ->belongsToMany(Contact::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }
}
