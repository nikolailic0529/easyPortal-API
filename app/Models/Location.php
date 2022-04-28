<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssets;
use App\Models\Relations\HasCustomers;
use App\Models\Relations\HasResellers;
use App\Services\Organization\Eloquent\OwnedByOrganization;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\LocationFactory;
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
 * @property mixed|null                     $latitude
 * @property mixed|null                     $longitude
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
 * @method static Builder|Location newModelQuery()
 * @method static Builder|Location newQuery()
 * @method static Builder|Location query()
 */
class Location extends Model implements OwnedByOrganization {
    use HasFactory;
    use HasAssets;

    /**
     * @phpstan-use \App\Models\Relations\HasResellers<\App\Models\LocationReseller>
     */
    use HasResellers;

    /**
     * @phpstan-use \App\Models\Relations\HasCustomers<\App\Models\LocationCustomer>
     */
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
