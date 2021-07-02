<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasTypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Location.
 *
 * @property string                                                              $id
 * @property string|null                                                         $object_id
 * @property string                                                              $object_type
 * @property string                                                              $country_id
 * @property string                                                              $city_id
 * @property string                                                              $postcode
 * @property string                                                              $state
 * @property string                                                              $line_one
 * @property string                                                              $line_two
 * @property string|null                                                         $latitude
 * @property string|null                                                         $longitude
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property \App\Models\City                                                    $city
 * @property \App\Models\Country                                                 $country
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Customer> $customers
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Type>          $types
 * @method static \Database\Factories\LocationFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location query()
 * @mixin \Eloquent
 */
class Location extends PolymorphicModel {
    use HasFactory;
    use HasAssets;
    use HasTypes;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'locations';

    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo {
        return $this->belongsTo(City::class);
    }

    public function setCountryAttribute(Country $country): void {
        $this->country()->associate($country);
    }

    public function setCityAttribute(City $country): void {
        $this->city()->associate($country);
    }

    protected function getTypesPivot(): Pivot {
        return new LocationType();
    }

    public function customers(): HasMany {
        return $this->hasMany(Customer::class, $this->getKeyName(), 'object_id');
    }
}
