<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

use function sprintf;

/**
 * Asset.
 *
 * @property string                       $id
 * @property string                       $oem_id
 * @property string                       $product_id
 * @property string|null                  $customer_id current
 * @property string|null                  $location_id current
 * @property string                       $serial_number
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Customer|null    $customer
 * @property \App\Models\Location|null    $location
 * @property \App\Models\Oem              $oem
 * @property \App\Models\Product          $product
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereSerialNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Asset whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Asset extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'assets';

    public function oem(): BelongsTo {
        return $this->belongsTo(Oem::class);
    }

    public function setOemAttribute(Oem $oem): void {
        $this->oem()->associate($oem);
    }

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    public function setProductAttribute(Product $product): void {
        $this->product()->associate($product);
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function setCustomerAttribute(?Customer $customer): void {
        $this->customer()->associate($customer);
    }

    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(?Location $location): void {
        // On the current phase  we assumes that assert may be located only on
        // location which related to the customer.
        if ($location) {
            $type        = (new Customer())->getMorphClass();
            $isIdMatch   = $location->object_id === $this->customer_id;
            $isTypeMatch = $location->object_type === $type;

            if (!$isIdMatch || !$isTypeMatch) {
                throw new InvalidArgumentException(sprintf(
                    'Location must be related to the `%s#%s` but it related to `%s#%s`.',
                    $type,
                    $this->customer_id,
                    $location->object_type,
                    $location->object_id,
                ));
            }
        }

        // Set
        $this->location()->associate($location);
    }
}
