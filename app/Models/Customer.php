<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Status;
use App\Models\Relations\HasAssets;
use App\Models\Relations\HasChangeRequests;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasContracts;
use App\Models\Relations\HasKpi;
use App\Models\Relations\HasLocations;
use App\Models\Relations\HasQuotes;
use App\Models\Relations\HasResellers;
use App\Models\Relations\HasStatuses;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Eloquent\SearchableImpl;
use App\Services\Search\Properties\Relation;
use App\Services\Search\Properties\Text;
use App\Utils\Eloquent\Concerns\SyncHasMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer.
 *
 * @property string                           $id
 * @property string                           $name
 * @property string|null                      $kpi_id
 * @property int                              $assets_count
 * @property int                              $quotes_count
 * @property int                              $contracts_count
 * @property int                              $locations_count
 * @property int                              $contacts_count
 * @property int                              $statuses_count
 * @property CarbonImmutable|null             $changed_at
 * @property CarbonImmutable|null             $synced_at
 * @property CarbonImmutable                  $created_at
 * @property CarbonImmutable                  $updated_at
 * @property CarbonImmutable|null             $deleted_at
 * @property-read Collection<int, Asset>      $assets
 * @property Collection<int, Contact>         $contacts
 * @property-read Collection<int, Document>   $contracts
 * @property-read CustomerLocation|null       $headquarter
 * @property Kpi|null                         $kpi
 * @property Collection<int,CustomerLocation> $locations
 * @property-read Collection<int, Document>   $quotes
 * @property-read Collection<int, Reseller>   $resellers
 * @property Collection<int, Status>          $statuses
 * @method static CustomerFactory factory(...$parameters)
 * @method static Builder<Customer>|Customer newModelQuery()
 * @method static Builder<Customer>|Customer newQuery()
 * @method static Builder<Customer>|Customer query()
 */
class Customer extends Model implements OwnedByReseller, Searchable {
    use OwnedByResellerImpl;
    use SearchableImpl;
    use HasFactory;
    use HasStatuses;
    use HasAssets;
    use HasContacts;
    use HasContracts;
    use HasQuotes;
    use HasKpi;
    use SyncHasMany;
    use HasChangeRequests;

    /**
     * @use HasLocations<CustomerLocation>
     */
    use HasLocations;

    /**
     * @use HasResellers<ResellerCustomer>
     */
    use HasResellers;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'changed_at' => 'datetime',
        'synced_at'  => 'datetime',
    ];

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
    public static function getSearchProperties(): array {
        // WARNING: If array is changed the search index MUST be rebuilt.
        return [
            'name'        => new Text('name', true),
            'headquarter' => new Relation('headquarter.location', [
                'city' => new Relation('city', [
                    'name' => new Text('name'),
                ]),
            ]),
        ];
    }
    // </editor-fold>

    // <editor-fold desc="OwnedByOrganization">
    // =========================================================================
    public static function getOwnedByResellerColumn(): string {
        $key    = (new static())->resellers()->getModel()->getKeyName();
        $column = "resellers.{$key}";

        return $column;
    }
    // </editor-fold>
}
