<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\Status;
use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCustomers;
use App\Models\Relations\HasDocuments;
use App\Models\Relations\HasKpi;
use App\Models\Relations\HasLocations;
use App\Models\Relations\HasStatuses;
use App\Services\Organization\Eloquent\OwnedByReseller;
use App\Services\Organization\Eloquent\OwnedByResellerImpl;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ResellerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reseller.
 *
 * @property string                            $id
 * @property string                            $name
 * @property string|null                       $kpi_id
 * @property int                               $customers_count
 * @property int                               $locations_count
 * @property int                               $assets_count
 * @property int                               $contacts_count
 * @property int                               $statuses_count
 * @property CarbonImmutable|null              $changed_at
 * @property CarbonImmutable|null              $synced_at
 * @property CarbonImmutable                   $created_at
 * @property CarbonImmutable                   $updated_at
 * @property CarbonImmutable|null              $deleted_at
 * @property-read Collection<int, Asset>       $assets
 * @property Collection<int, Contact>          $contacts
 * @property-read Collection<int, Customer>    $customers
 * @property-read Collection<int, Document>    $documents
 * @property-read ResellerLocation|null        $headquarter
 * @property Kpi|null                          $kpi
 * @property Collection<int, ResellerLocation> $locations
 * @property Collection<int, Status>           $statuses
 * @method static ResellerFactory factory(...$parameters)
 * @method static Builder<Reseller>|Reseller newModelQuery()
 * @method static Builder<Reseller>|Reseller newQuery()
 * @method static Builder<Reseller>|Reseller query()
 */
class Reseller extends Model implements OwnedByReseller {
    use OwnedByResellerImpl;
    use HasFactory;
    use HasAssets;
    use HasStatuses;
    use HasContacts;
    use HasKpi;
    use HasDocuments;
    use SyncBelongsToMany;

    /**
     * @use HasLocations<ResellerLocation>
     */
    use HasLocations;

    /**
     * @use HasCustomers<ResellerCustomer>
     */
    use HasCustomers;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'resellers';

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
    /**
     * @private required to delete associated {@see Organization} record.
     *
     * @return BelongsTo<Organization, self>
     */
    #[CascadeDelete]
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class, $this->getKeyName());
    }
    // </editor-fold>

    // <editor-fold desc="HasStatuses">
    // =========================================================================
    protected function getStatusesPivot(): Pivot {
        return new ResellerStatus();
    }
    // </editor-fold>

    // <editor-fold desc="HasCustomers">
    // =========================================================================
    protected function getCustomersPivot(): Pivot {
        return new ResellerCustomer();
    }
    // </editor-fold>

    // <editor-fold desc="HasLocations">
    // =========================================================================
    protected function getLocationsModel(): Model {
        return new ResellerLocation();
    }
    // </editor-fold>

    // <editor-fold desc="OwnedByOrganization">
    // =========================================================================
    public static function getOwnedByResellerColumn(): string {
        return (new static())->getKeyName();
    }
    // </editor-fold>
}
