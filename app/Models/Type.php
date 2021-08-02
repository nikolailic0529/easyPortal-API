<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\Relations\HasAssets;
use App\Models\Concerns\Relations\HasContracts;
use App\Models\Concerns\Relations\HasQuotes;
use App\Models\Concerns\TranslateProperties;
use App\Models\Scopes\ContractType;
use App\Models\Scopes\ContractTypeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use function app;

/**
 * Type.
 *
 * @property string                                                              $id
 * @property string                                                              $object_type
 * @property string                                                              $key
 * @property string                                                              $name
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>  $contacts
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Document>      $contracts
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Customer> $customers
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Location> $locations
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document> $quotes
 * @method static \Database\Factories\TypeFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type queryContracts()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type query()
 * @mixin \Eloquent
 */
class Type extends PolymorphicModel implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssets;
    use HasContracts;
    use HasQuotes;
    use ContractTypeScope;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'types';

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }

    public function locations(): BelongsToMany {
        $pivot = new LocationType();

        return $this
            ->belongsToMany(Location::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    public function contacts(): BelongsToMany {
        $pivot = new ContactType();

        return $this
            ->belongsToMany(Contact::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    public function customers(): HasMany {
        return $this->hasMany(Customer::class);
    }
}
