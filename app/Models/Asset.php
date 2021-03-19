<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasCustomer;
use App\Models\Concerns\HasOem;
use App\Models\Concerns\HasProduct;
use App\Models\Concerns\HasReseller;
use App\Models\Concerns\HasType;
use App\Models\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

use function in_array;
use function is_null;
use function sprintf;

/**
 * Asset.
 *
 * @property string                                                                   $id
 * @property string                                                                   $oem_id
 * @property string                                                                   $product_id
 * @property string                                                                   $type_id
 * @property string|null                                                              $reseller_id current
 * @property string|null                                                              $customer_id current
 * @property string|null                                                              $location_id current
 * @property string                                                                   $serial_number
 * @property \Carbon\CarbonImmutable                                                  $created_at
 * @property \Carbon\CarbonImmutable                                                  $updated_at
 * @property \Carbon\CarbonImmutable|null                                             $deleted_at
 * @property \App\Models\Customer|null                                                $customer
 * @property \App\Models\Location|null                                                $location
 * @property \App\Models\Oem                                                          $oem
 * @property \App\Models\Product                                                      $product
 * @property \App\Models\Reseller|null                                                $reseller
 * @property \App\Models\Type                                                         $type
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\AssetWarranty> $warranties
 * @property-read int|null                                                            $warranties_count
 * @method static \Database\Factories\AssetFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereOemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereResellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Asset extends Model {
    use HasFactory;
    use HasOem;
    use HasType;
    use HasProduct;
    use HasReseller;
    use HasCustomer;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'assets';

    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(?Location $location): void {
        // Assert may be located on
        // - customer location
        // - reseller location
        // - own location
        if ($location) {
            $asset       = (new Asset())->getMorphClass();
            $customer    = (new Customer())->getMorphClass();
            $reseller    = (new Reseller())->getMorphClass();
            $isIdMatch   = is_null($location->object_id)
                || in_array($location->object_id, [$this->customer_id, $this->reseller_id], true);
            $isTypeMatch = in_array($location->object_type, [$asset, $customer, $reseller], true);

            if (!$isIdMatch || !$isTypeMatch) {
                throw new InvalidArgumentException(sprintf(
                    'Location must be related to the `%s` or `%s` or `%s` but it related to `%s#%s`.',
                    $customer.($this->customer_id ? "#{$this->customer_id}" : ''),
                    $reseller.($this->reseller_id ? "#{$this->reseller_id}" : ''),
                    $asset,
                    $location->object_type,
                    $location->object_id,
                ));
            }
        }

        // Set
        $this->location()->associate($location);
    }

    public function warranties(): HasMany {
        return $this->hasMany(AssetWarranty::class);
    }

    /**
     * @inheritdoc
     */
    protected function getValidProductTypes(): array {
        return [ProductType::asset()];
    }
}
