<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssetsThroughLocations;
use App\Models\Relations\HasCustomersThroughLocations;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * City.
 *
 * @property string                        $id
 * @property string                        $country_id
 * @property string                        $key
 * @property string                        $name
 * @property \Carbon\CarbonImmutable       $created_at
 * @property \Carbon\CarbonImmutable       $updated_at
 * @property \Carbon\CarbonImmutable|null  $deleted_at
 * @property-read Collection<int,Asset>    $assets
 * @property \App\Models\Country           $country
 * @property-read Collection<int,Customer> $customers
 * @method static \Database\Factories\CityFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\City query()
 * @mixin \Eloquent
 */
class City extends Model {
    use HasFactory;
    use HasAssetsThroughLocations;
    use HasCustomersThroughLocations;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'cities';

    #[CascadeDelete(false)]
    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    public function setCountryAttribute(Country $country): void {
        $this->country()->associate($country);
    }
}
