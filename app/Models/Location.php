<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * Location.
 *
 * @property string                                                     $id
 * @property string                                                     $object_id
 * @property string                                                     $object_type
 * @property string                                                     $country_id
 * @property string                                                     $city_id
 * @property string                                                     $postcode
 * @property string                                                     $state
 * @property string                                                     $line_one
 * @property string                                                     $line_two
 * @property string|null                                                $lat
 * @property string|null                                                $lng
 * @property \Carbon\CarbonImmutable                                    $created_at
 * @property \Carbon\CarbonImmutable                                    $updated_at
 * @property \Carbon\CarbonImmutable|null                               $deleted_at
 * @property \App\Models\City                                           $city
 * @property \App\Models\Country                                        $country
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Type> $types
 * @property-read int|null                                              $types_count
 * @method static \Database\Factories\LocationFactory factory(...$parameters)
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location wherePostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Location whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Location extends PolymorphicModel {
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

    public function types(): BelongsToMany {
        return $this->belongsToMany(Type::class, 'location_types')->withTimestamps();
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Type>|array<\App\Models\Type> $types
     */
    public function setTypesAttribute(Collection|array $types): void {
        $types = new Collection($types);

        $this->setRelation('types', $types);
        $this->types()->sync($types->map(static function (Type $type): string {
            return $type->getKey();
        })->all());
    }
}
