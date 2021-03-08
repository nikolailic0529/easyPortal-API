<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

use function count;
use function sprintf;

/**
 * Customer.
 *
 * @property string                                                           $id
 * @property string                                                           $type_id
 * @property string                                                           $status_id
 * @property string                                                           $name
 * @property int                                                              $locations_count
 * @property int                                                              $assets_count
 * @property \Carbon\CarbonImmutable                                          $created_at
 * @property \Carbon\CarbonImmutable                                          $updated_at
 * @property \Carbon\CarbonImmutable|null                                     $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<\App\Models\Asset> $assets
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>    $contacts
 * @property-read int|null                                                    $contacts_count
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>   $locations
 * @property \App\Models\Status                                               $status
 * @property \App\Models\Type                                                 $type
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereAssetsCount($value)
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

    public function assets(): HasMany {
        return $this->hasMany(Asset::class);
    }

    public function locations(): MorphMany {
        return $this->morphMany(Location::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\Location> $locations
     */
    public function setLocationsAttribute(Collection|array $locations): void {
        $this->syncMorphMany('locations', $locations);
        $this->locations_count = count($locations);
    }

    public function contacts(): MorphMany {
        return $this->morphMany(Contact::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\Contact> $contacts
     */
    public function setContactsAttribute(Collection|array $contacts): void {
        $this->syncMorphMany('contacts', $contacts);
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\PolymorphicModel> $objects
     */
    protected function syncMorphMany(string $relation, Collection|array $objects): void {
        // TODO [refactor] Probably we need move it into MorphMany class

        // Prepare
        /** @var \Illuminate\Database\Eloquent\Relations\MorphMany $morph */
        $morph = $this->{$relation}();
        $model = $morph->make();
        $class = $model::class;

        if (!($morph instanceof MorphMany)) {
            throw new InvalidArgumentException(sprintf(
                'The `$relation` must be instance of `%s`.',
                MorphMany::class,
            ));
        }

        if (!($model instanceof PolymorphicModel)) {
            throw new InvalidArgumentException(sprintf(
                'Related model should be instance of `%s`.',
                PolymorphicModel::class,
            ));
        }

        // Object should exist
        if (!$this->exists) {
            $this->save();
        }

        // Create/Update existing
        $existing = (clone $this->{$relation})->keyBy(static function (PolymorphicModel $contact): string {
            return $contact->getKey();
        });

        foreach ($objects as $object) {
            // Object supported by relation?
            if (!($object instanceof $class)) {
                throw new InvalidArgumentException(sprintf(
                    'Object should be instance of `%s`.',
                    $class,
                ));
            }

            // We should not update the existing object if it is related to
            // another object type. Probably this is an error.
            if (
                ($object->object_type && $object->object_type !== $this->getMorphClass())
                || ($object->object_id && $object->object_id !== $this->getKey())
            ) {
                throw new InvalidArgumentException(sprintf(
                    'Object related to %s#%s, %s#%s or `null` required.',
                    $object->object_type,
                    $object->object_id,
                    $this->getMorphClass(),
                    $this->getKey(),
                ));
            }

            // Save
            $morph->save($object);

            // Mark as used
            $existing->forget($object->getKey());
        }

        // Delete unused
        foreach ($existing as $object) {
            $object->delete();
        }

        // Reset relation
        unset($this->{$relation});
    }
}
