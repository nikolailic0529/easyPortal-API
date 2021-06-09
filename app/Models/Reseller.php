<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasContacts;
use App\Models\Concerns\HasLocations;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use App\Models\Concerns\SyncBelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

use function count;

/**
 * Reseller.
 *
 * @property string                                                           $id
 * @property string|null                                                      $type_id
 * @property string|null                                                      $status_id
 * @property string                                                           $name
 * @property int                                                              $customers_count
 * @property int                                                              $locations_count
 * @property int                                                              $assets_count
 * @property int                                                              $contacts_count
 * @property \Carbon\CarbonImmutable                                          $created_at
 * @property \Carbon\CarbonImmutable                                          $updated_at
 * @property \Carbon\CarbonImmutable|null                                     $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset> $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>    $contacts
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Customer>   $customers
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>   $locations
 * @property \App\Models\Status|null                                          $status
 * @property \App\Models\Type|null                                            $type
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
    use HasStatus;
    use HasContacts;
    use SyncBelongsToMany;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'resellers';

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
}
