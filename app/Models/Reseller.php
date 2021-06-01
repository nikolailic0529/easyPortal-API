<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasContacts;
use App\Models\Concerns\HasLocations;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Reseller.
 *
 * @property string                                                              $id
 * @property string                                                              $name
 * @property int|null                                                            $customers_count
 * @property int|null                                                            $locations_count
 * @property int|null                                                            $assets_count
 * @property int|null                                                            $status_id
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Customer> $customers
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>      $locations
 * @property \App\Models\Status                                                  $status
 * @method static \Database\Factories\ResellerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereAssetsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereCustomersCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereLocationsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Reseller whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Reseller extends Model {
    use HasFactory;
    use HasAssets;
    use HasLocations;
    use HasType;
    use HasStatus;
    use HasContacts;

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
}
