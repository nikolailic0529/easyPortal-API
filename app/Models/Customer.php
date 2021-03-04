<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>          $contacts
 * @property-read int|null                                                          $contacts_count
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
     * @param \Illuminate\Support\Collection|array<\App\Models\CustomerLocation> $locations
     */
    public function setLocationsAttribute(Collection|array $locations): void {
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

        // Reset relation
        unset($this->locations);
    }

    public function contacts(): MorphMany {
        return $this->morphMany(Contact::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection<\App\Models\Contact>|array<\App\Models\Contact> $contacts
     */
    public function setContactsAttribute(Collection|array $contacts): void {
        // Create/Update existing
        $relation = $this->contacts();
        $existing = (clone $this->contacts)->keyBy(static function (Contact $contact): string {
            return $contact->getKey();
        });

        foreach ($contacts as $contact) {
            // We should not update the existing object if it is related to
            // another object type. Probably this is an error.

            if (
                ($contact->object_type && $contact->object_type !== $this->getMorphClass())
                || ($contact->object_id && $contact->object_id !== $this->getKey())
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Location related to Customer #%s, Customer #%s or `null` required.',
                    $contact->customer_id,
                    $this->getKey(),
                ));
            }

            // Save
            $relation->save($contact);

            // Mark as used
            $existing->forget($contact->getKey());
        }

        // Delete unused
        foreach ($existing as $contact) {
            $contact->delete();
        }

        // Reset relation
        unset($this->contacts);
    }
}
