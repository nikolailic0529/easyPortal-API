<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\LocationCustomer;
use App\Models\LocationReseller;
use App\Models\Relations\HasAssets;
use App\Models\Relations\HasCustomers;
use App\Models\Relations\HasResellers;
use App\Models\Reseller;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\Data\LocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Location.
 *
 * @property string                         $id
 * @property string                         $country_id
 * @property string                         $city_id
 * @property string                         $postcode
 * @property string                         $state
 * @property string                         $line_one
 * @property string                         $line_two
 * @property string|null                    $latitude
 * @property string|null                    $longitude
 * @property string|null                    $geohash
 * @property int                            $customers_count
 * @property int                            $assets_count
 * @property CarbonImmutable                $created_at
 * @property CarbonImmutable                $updated_at
 * @property CarbonImmutable|null           $deleted_at
 * @property-read Collection<int, Asset>    $assets
 * @property City                           $city
 * @property Country                        $country
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Reseller> $resellers
 * @method static LocationFactory factory(...$parameters)
 * @method static Builder<Location>|Location newModelQuery()
 * @method static Builder<Location>|Location newQuery()
 * @method static Builder<Location>|Location query()
 */
class Location extends Model implements DataModel {
    use HasFactory;
    use HasAssets;

    /**
     * @phpstan-use HasResellers<LocationReseller>
     */
    use HasResellers;

    /**
     * @phpstan-use HasCustomers<LocationCustomer>
     */
    use HasCustomers;

    public const GEOHASH_LENGTH = 12;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * The attributes that should be cast to native types.
     *
     * @inheritdoc
     */
    protected $casts = [
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * @return BelongsTo<Country, self>
     */
    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<City, self>
     */
    public function city(): BelongsTo {
        return $this->belongsTo(City::class);
    }

    public function setCountryAttribute(Country $country): void {
        $this->country()->associate($country);
    }

    public function setCityAttribute(City $city): void {
        $this->city()->associate($city);
    }

    protected function getResellersPivot(): Pivot {
        return new LocationReseller();
    }

    protected function getCustomersPivot(): Pivot {
        return new LocationCustomer();
    }
}
