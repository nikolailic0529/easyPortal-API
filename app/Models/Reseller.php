<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\Relations\HasAssets;
use App\Models\Concerns\Relations\HasContacts;
use App\Models\Concerns\Relations\HasKpi;
use App\Models\Concerns\Relations\HasLocations;
use App\Models\Concerns\Relations\HasStatuses;
use App\Models\Concerns\Relations\HasType;
use App\Models\Concerns\SyncBelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

use function count;

/**
 * Reseller.
 *
 * @property string                                                           $id
 * @property string                                                           $type_id
 * @property string                                                           $name
 * @property int                                                              $customers_count
 * @property int                                                              $locations_count
 * @property int                                                              $assets_count
 * @property int                                                              $contacts_count
 * @property \Carbon\CarbonImmutable|null                                     $changed_at
 * @property \Carbon\CarbonImmutable                                          $created_at
 * @property \Carbon\CarbonImmutable                                          $updated_at
 * @property \Carbon\CarbonImmutable|null                                     $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset> $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>    $contacts
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Customer>   $customers
 * @property \App\Models\Kpi|null                                             $kpi
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>   $locations
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Status>     $statuses
 * @property \App\Models\Type                                                 $type
 * @method static \Database\Factories\ResellerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller query()
 * @mixin \Eloquent
 */
class Reseller extends Model {
    use HasFactory;
    use HasAssets;
    use HasLocations;
    use HasType;
    use HasStatuses;
    use HasContacts;
    use HasKpi;
    use SyncBelongsToMany;

    protected const CASTS = [
        'changed_at' => 'datetime',
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

    public function customers(): BelongsToMany {
        $pivot = new ResellerCustomer();

        return $this
            ->belongsToMany(Customer::class, $pivot->getTable())
            ->using($pivot::class)
            ->wherePivotNull($pivot->getDeletedAtColumn())
            ->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Reseller>|array<\App\Models\Reseller> $customers
     */
    public function setCustomersAttribute(Collection|array $customers): void {
        $this->syncBelongsToMany('customers', $customers);
        $this->customers_count = count($customers);
    }

    protected function getStatusesPivot(): Pivot {
        return new ResellerStatus();
    }
}
