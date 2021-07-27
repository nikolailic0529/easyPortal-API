<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasContacts;
use App\Models\Concerns\HasContracts;
use App\Models\Concerns\HasLocations;
use App\Models\Concerns\HasQuotes;
use App\Models\Concerns\HasStatuses;
use App\Models\Concerns\HasType;
use App\Services\Organization\Eloquent\OwnedByReseller;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Scout\Searchable;

use function app;

/**
 * Customer.
 *
 * @property string                                                              $id
 * @property string                                                              $type_id
 * @property string                                                              $name
 * @property int                                                                 $assets_count
 * @property int                                                                 $locations_count
 * @property int                                                                 $contacts_count
 * @property \Carbon\CarbonImmutable|null                                        $changed_at
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>       $contacts
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document> $contracts
 * @property-read \App\Models\Location|null                                      $headquarter
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>      $locations
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document> $quotes
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Reseller> $resellers
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Status>        $statuses
 * @property \App\Models\Type                                                    $type
 * @method static \Database\Factories\CustomerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @mixin \Eloquent
 */
class Customer extends Model {
    use Searchable;
    use OwnedByReseller;
    use HasFactory;
    use HasType;
    use HasStatuses;
    use HasAssets;
    use HasLocations;
    use HasContacts;
    use HasContracts;
    use HasQuotes;

    protected const CASTS = [
        'changed_at' => 'datetime',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    // <editor-fold desc="Relations">
    // =========================================================================
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

    protected function getStatusesPivot(): Pivot {
        return new CustomerStatus();
    }
    // </editor-fold>

    // <editor-fold desc="OwnedByOrganization">
    // =========================================================================
    public function getOrganizationThrough(): ?Relation {
        return $this->hasMany(ResellerCustomer::class);
    }
    // </editor-fold>
}
