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
 * @property \Carbon\CarbonImmutable|null      $changed_at
 * @property \Carbon\CarbonImmutable           $synced_at
 * @property \Carbon\CarbonImmutable           $created_at
 * @property \Carbon\CarbonImmutable           $updated_at
 * @property \Carbon\CarbonImmutable|null      $deleted_at
 * @property-read Collection<int, Asset>       $assets
 * @property Collection<int, Contact>          $contacts
 * @property-read Collection<int, Customer>    $customers
 * @property-read ResellerLocation|null        $headquarter
 * @property \App\Models\Kpi|null              $kpi
 * @property Collection<int, ResellerLocation> $locations
 * @property Collection<int, Status>           $statuses
 * @property \App\Models\Type                  $type
 * @method static \Database\Factories\ResellerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller query()
 * @mixin \Eloquent
 *
 * @uses \App\Models\Relations\HasCustomers<\App\Models\ResellerCustomer>
 */
class Reseller extends Model {
    use HasFactory;
    use HasAssets;
    use HasLocations;
    use HasCustomers;
    use HasType;
    use HasStatuses;
    use HasContacts;
    use HasKpi;
    use SyncBelongsToMany;

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
