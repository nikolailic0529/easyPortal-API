<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Queries\ContractTypes;
use App\GraphQL\Queries\QuoteTypes;
use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasContacts;
use App\Models\Concerns\HasContracts;
use App\Models\Concerns\HasLocations;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;

use function app;

/**
 * Customer.
 *
 * @property string                                                              $id
 * @property string                                                              $type_id
 * @property string                                                              $status_id
 * @property string                                                              $name
 * @property int                                                                 $locations_count
 * @property int                                                                 $assets_count
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>       $contacts
 * @property int                                                                 $contacts_count
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Document>      $contracts
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>      $locations
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document> $quotes
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Reseller> $resellers
 * @property \App\Models\Status                                                  $status
 * @property \App\Models\Type                                                    $type
 * @method static \Database\Factories\CustomerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereAssetsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereContactsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereLocationsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read int|null                                                       $contracts_count
 * @property-read \App\Models\Location|null                                      $headquarter
 * @property-read int|null                                                       $quotes_count
 * @property-read int|null                                                       $resellers_count
 */
class Customer extends Model {
    use OwnedByOrganization;
    use HasFactory;
    use HasType;
    use HasStatus;
    use HasAssets;
    use HasLocations;
    use HasContacts;
    use HasContracts;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customers';

    public function headquarter(): MorphOne {
        $type = app()->make(Repository::class)->get('ep.headquarter_type');

        return $this
            ->morphOne(Location::class, 'object')
            ->whereHas('types', static function ($query) use ($type) {
                return $query->whereKey($type);
            });
    }

    public function resellers(): BelongsToMany {
        $pivot = new ResellerCustomer();

        return $this
            ->belongsToMany(Reseller::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    public function quotes(): HasMany {
        return $this
            ->hasMany(Document::class)
            ->where(static function (Builder $builder): Builder {
                return app()->make(QuoteTypes::class)->prepare($builder);
            });
    }

    // <editor-fold desc="OwnedByOrganization">
    // =========================================================================
    public function getQualifiedOrganizationColumn(): string {
        return $this->getOrganizationThrough()->getModel()->qualifyColumn('reseller_id');
    }

    public function getOrganizationThrough(): ?Relation {
        return $this->hasMany(ResellerCustomer::class);
    }
    // </editor-fold>
}
