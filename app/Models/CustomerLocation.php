<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CustomerLocation.
 *
 * @property string                       $id
 * @property string                       $customer_id
 * @property string                       $location_id
 * @property string                       $type_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Customer         $customer
 * @property \App\Models\Location         $location
 * @property \App\Models\Type             $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CustomerLocation extends Model {
    use HasFactory;
    use HasType;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_locations';

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function setCustomerAttribute(Customer $customer): void {
        $this->customer()->associate($customer);
    }

    public function location(): BelongsTo {
        return $this->belongsTo(Location::class);
    }

    public function setLocationAttribute(Location $location): void {
        $this->location()->associate($location);
    }
}
