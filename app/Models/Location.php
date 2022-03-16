<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasCustomers;
use App\Models\Relations\HasResellers;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
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
 * @property mixed|null                     $latitude
 * @property mixed|null                     $longitude
 * @property string|null                    $geohash
 * @property int                            $customers_count
 * @property int                            $assets_count
 * @property \Carbon\CarbonImmutable        $created_at
 * @property \Carbon\CarbonImmutable        $updated_at
 * @property \Carbon\CarbonImmutable|null   $deleted_at
 * @property-read Collection<int, Asset>    $assets
 * @property \App\Models\City               $city
 * @property \App\Models\Country            $country
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Reseller> $resellers
 * @method static \Database\Factories\LocationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location query()
 * @mixin \Eloquent
 *
 * @uses \App\Models\Relations\HasCustomers<\App\Models\LocationCustomer>
 * @uses \App\Models\Relations\HasResellers<\App\Models\LocationReseller>
 */
class Location extends Model {
    use HasFactory;
    use HasAssets;
    use HasResellers;
    use HasCustomers;

    public const GEOHASH_LENGTH = 12;

    /**
     * The attributes that should be cast to native types.
     */
    protected const CASTS = [
        'latitude'  => 'decimal:8',
        'longitude' => 'decimal:8',
    ] + parent::CASTS;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'locations';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<string>
     */
    protected $casts = self::CASTS;

    #[CascadeDelete(false)]
    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    #[CascadeDelete(false)]
    public function city(): BelongsTo {
        return $this->belongsTo(City::class);
    }

    public function setCountryAttribute(Country $country): void {
        $this->country()->associate($country);
    }

    public function setCityAttribute(City $country): void {
        $this->city()->associate($country);
    }

    protected function getResellersPivot(): Pivot {
        return new LocationReseller();
    }

    protected function getCustomersPivot(): Pivot {
        return new LocationCustomer();
    }
}
