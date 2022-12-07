<?php declare(strict_types = 1);

namespace App\Models\Data;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Relations\HasAssetsThroughLocations;
use App\Models\Relations\HasCities;
use App\Models\Relations\HasCustomersThroughLocations;
use App\Services\I18n\Contracts\Translatable;
use App\Services\I18n\Eloquent\TranslateProperties;
use App\Utils\Eloquent\Contracts\DataModel;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\Data\CountryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Country.
 *
 * @property string                        $id
 * @property string                        $code
 * @property string                        $name
 * @property CarbonImmutable               $created_at
 * @property CarbonImmutable               $updated_at
 * @property CarbonImmutable|null          $deleted_at
 * @property-read Collection<int,Asset>    $assets
 * @property-read Collection<int,City>     $cities
 * @property-read Collection<int,Customer> $customers
 * @method static CountryFactory factory(...$parameters)
 * @method static Builder<Country>|Country newModelQuery()
 * @method static Builder<Country>|Country newQuery()
 * @method static Builder<Country>|Country query()
 */
class Country extends Model implements DataModel, Translatable {
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

    protected function getTranslatableKey(): ?string {
        return $this->code;
    }

    /**
     * @inheritdoc
     */
    protected function getTranslatableProperties(): array {
        return ['name'];
    }
}
