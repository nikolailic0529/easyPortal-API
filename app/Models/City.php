<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Concerns\TranslateProperties;
use App\Models\Relations\HasAssetsThroughLocations;
use App\Models\Relations\HasCustomersThroughLocations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * City.
 *
 * @property string                                                              $id
 * @property string                                                              $country_id
 * @property string                                                              $name
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property \App\Models\Country                                                 $country
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Customer> $customers
 * @method static \Database\Factories\CityFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\City newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\City newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\City query()
 * @mixin \Eloquent
 */
class City extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasAssetsThroughLocations;
    use HasCustomersThroughLocations;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'cities';

    public function country(): BelongsTo {
        return $this->belongsTo(Country::class);
    }

    public function setCountryAttribute(Country $country): void {
        $this->country()->associate($country);
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
