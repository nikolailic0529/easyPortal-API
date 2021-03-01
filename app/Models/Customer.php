<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function sprintf;

/**
 * Customer.
 *
 * @property string                                                                 $id
 * @property string                                                                 $type_id
 * @property string                                                                 $status_id
 * @property string                                                                 $name
 * @property \Carbon\CarbonImmutable                                                $created_at
 * @property \Carbon\CarbonImmutable                                                $updated_at
 * @property \Carbon\CarbonImmutable|null                                           $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\CustomerLocation> $locations
 * @property-read int|null                                                          $locations_count
 * @property \App\Models\Status                                                     $status
 * @property \App\Models\Type                                                       $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Customer extends Model {
    use HasFactory;
    use HasType;
    use HasStatus;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customers';

    public function locations(): HasMany {
        return $this->hasMany(CustomerLocation::class);
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\CustomerLocation> $locations
     */
    public function setLocationsAttribute(Collection $locations): void {
        // Create/Update existing
        $existing = (clone $this->locations)->keyBy(static function (CustomerLocation $location): string {
            return $location->getKey();
        });

        foreach ($locations as $location) {
            // We should not update the existing location if it is related to
            // another customer. Probably this is an error.
            if ($location->customer_id && $location->customer_id !== $this->getKey()) {
                throw new InvalidArgumentException(sprintf(
                    'Location related to Customer #%s, Customer #%s or `null` required.',
                    $location->customer_id,
                    $this->getKey(),
                ));
            }

            // Save
            $location->customer_id = $this->getKey();
            $location->save();

            // Mark as used
            $existing->forget($location->getKey());
        }

        // Delete unused
        foreach ($existing as $location) {
            $location->delete();
        }
    }
}
