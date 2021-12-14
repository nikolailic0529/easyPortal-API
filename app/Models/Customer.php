<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasContracts;
use App\Models\Relations\HasKpi;
use App\Models\Relations\HasLocations;
use App\Models\Relations\HasQuotes;
use App\Models\Relations\HasResellers;
use App\Models\Relations\HasStatuses;
use App\Models\Relations\HasType;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer.
 *
 * @property string                                                                                                               $id
 * @property string                                                                                                               $type_id
 * @property string                                                                                                               $name
 * @property string|null                                                                                                          $kpi_id
 * @property int                                                                                                                  $assets_count
 * @property int                                                                                                                  $locations_count
 * @property int                                                                                                                  $contacts_count
 * @property int                                                                                                                  $statuses_count
 * @property \Carbon\CarbonImmutable|null                                                                                         $changed_at
 * @property \Carbon\CarbonImmutable                                                                                              $synced_at
 * @property \Carbon\CarbonImmutable                                                                                              $created_at
 * @property \Carbon\CarbonImmutable                                                                                              $updated_at
 * @property \Carbon\CarbonImmutable|null                                                                                         $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>                                                     $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>                                                        $contacts
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document>                                                  $contracts
 * @property-read \App\Models\CustomerLocation|null                                                                               $headquarter
 * @property \App\Models\Kpi|null                                                                                                 $kpi
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\CustomerLocation>                                               $locations
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Document>                                                  $quotes
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Reseller>                                                  $resellers
 * @property-write array<string,\App\Models\ResellerCustomer>|\Illuminate\Support\Collection<string,\App\Models\ResellerCustomer> $resellersPivots
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Status>                                                         $statuses
 * @property \App\Models\Type                                                                                                     $type
 * @method static \Database\Factories\CustomerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @mixin \Eloquent
 *
 * @uses \App\Models\Relations\HasResellers<\App\Models\ResellerCustomer>
 */
class Customer extends Model {
    use Searchable;
    use HasFactory;
    use HasType;
    use HasStatuses;
    use HasAssets;
    use HasResellers;
    use HasLocations;
    use HasContacts;
    use HasContracts;
    use HasQuotes;
    use HasKpi;
    use SyncHasMany;

    protected const CASTS = [
        'changed_at' => 'datetime',
        'synced_at'  => 'datetime',
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
    protected function getStatusesPivot(): Pivot {
        return new CustomerStatus();
    }

    protected function getResellersPivot(): Pivot {
        return new ResellerCustomer();
    }

    protected function getLocationsModel(): Model {
        return new CustomerLocation();
    }
    // </editor-fold>

    // <editor-fold desc="Searchable">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected static function getSearchProperties(): array {
        // WARNING: If array is changed the search index MUST be rebuilt.
        return [
            'name'        => new Text('name', true),
            'headquarter' => [
                'city' => [
                    'name' => new Text('headquarter.location.city.name'),
                ],
            ],
        ];
    }
    // </editor-fold>
}
