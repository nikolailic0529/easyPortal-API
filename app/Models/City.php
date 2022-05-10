<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAssetsThroughLocations;
use App\Models\Relations\HasCustomersThroughLocations;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Builder;
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
 * @property CarbonImmutable               $created_at
 * @property CarbonImmutable               $updated_at
 * @property CarbonImmutable|null          $deleted_at
 * @property-read Collection<int,Asset>    $assets
 * @property Country                       $country
 * @property-read Collection<int,Customer> $customers
 * @method static CityFactory factory(...$parameters)
 * @method static Builder|City newModelQuery()
 * @method static Builder|City newQuery()
 * @method static Builder|City query()
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
