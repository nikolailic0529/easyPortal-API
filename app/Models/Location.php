<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Location.
 *
 * @property string                       $id
 * @property string                       $country_id
 * @property string                       $city_id
 * @property string                       $postcode
 * @property string                       $state
 * @property string                       $line_one
 * @property string                       $line_two
 * @property string|null                  $lat
 * @property string|null                  $lng
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\City             $city
 * @property \App\Models\Country          $country
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereLineOne($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereLineTwo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Location extends Model {
    use HasFactory;

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
}
