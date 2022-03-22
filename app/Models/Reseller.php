<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasContacts;
use App\Models\Relations\HasCustomers;
use App\Models\Relations\HasKpi;
use App\Models\Relations\HasLocations;
use App\Models\Relations\HasStatuses;
use App\Models\Relations\HasType;
use App\Utils\Eloquent\Concerns\SyncBelongsToMany;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\ResellerFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Reseller.
 *
 * @property string                            $id
 * @property string                            $type_id
 * @property string                            $name
 * @property string|null                       $kpi_id
 * @property int                               $customers_count
 * @property int                               $locations_count
 * @property int                               $assets_count
 * @property int                               $contacts_count
 * @property int                               $statuses_count
 * @property CarbonImmutable|null              $changed_at
 * @property CarbonImmutable                   $synced_at
 * @property CarbonImmutable                   $created_at
 * @property CarbonImmutable                   $updated_at
 * @property CarbonImmutable|null              $deleted_at
 * @property-read Collection<int, Asset>       $assets
 * @property Collection<int, Contact>          $contacts
 * @property-read Collection<int, Customer>    $customers
 * @property-read ResellerLocation|null        $headquarter
 * @property Kpi|null                          $kpi
 * @property Collection<int, ResellerLocation> $locations
 * @property Collection<int, Status>           $statuses
 * @property Type                              $type
 * @method static ResellerFactory factory(...$parameters)
 * @method static Builder|Reseller newModelQuery()
 * @method static Builder|Reseller newQuery()
 * @method static Builder|Reseller query()
 * @mixin Eloquent
 */
class Reseller extends Model {
    use HasFactory;
    use HasAssets;
    use HasType;
    use HasStatuses;
    use HasContacts;
    use HasKpi;
    use SyncBelongsToMany;

    /**
     * @phpstan-use HasLocations<ResellerLocation>
     */
    use HasLocations;

    /**
     * @phpstan-use HasCustomers<ResellerCustomer>
     */
    use HasCustomers;

    protected const CASTS = [
        'changed_at' => 'datetime',
        'synced_at'  => 'datetime',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'resellers';

    /**
     * The attributes that should be cast to native types.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    protected function getStatusesPivot(): Pivot {
        return new ResellerStatus();
    }

    protected function getCustomersPivot(): Pivot {
        return new ResellerCustomer();
    }

    protected function getLocationsModel(): Model {
        return new ResellerLocation();
    }
}
