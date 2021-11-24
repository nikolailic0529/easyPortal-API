<?php declare(strict_types = 1);

namespace App\Models;

use App\GraphQL\Contracts\Translatable;
use App\Models\Relations\HasAssetsThroughLocations;
use App\Models\Relations\HasCities;
use App\Models\Relations\HasCustomersThroughLocations;
use App\Utils\Eloquent\Concerns\TranslateProperties;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Country.
 *
 * @property string                                                              $id
 * @property string                                                              $code
 * @property string                                                              $name
 * @property \Carbon\CarbonImmutable                                             $created_at
 * @property \Carbon\CarbonImmutable                                             $updated_at
 * @property \Carbon\CarbonImmutable|null                                        $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset>    $assets
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\City>     $cities
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Customer> $customers
 * @method static \Database\Factories\CountryFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Country newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Country newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Country query()
 * @mixin \Eloquent
 */
class Country extends Model implements Translatable {
    use HasFactory;
    use TranslateProperties;
    use HasCities;
    use HasAssetsThroughLocations;
    use HasCustomersThroughLocations;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'countries';

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
