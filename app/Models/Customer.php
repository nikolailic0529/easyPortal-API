<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAssets;
use App\Models\Concerns\HasLocations;
use App\Models\Concerns\HasStatus;
use App\Models\Concerns\HasType;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;

use function app;
use function count;

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
 * @property int                                                              $contacts_count
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location>   $locations
 * @property \App\Models\Status                                               $status
 * @property \App\Models\Type                                                 $type
 * @method static \Database\Factories\CustomerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereAssetsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereContactsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Customer whereLocationsCount($value)
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
    use HasAssets;
    use HasLocations;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customers';

    public function contacts(): MorphMany {
        return $this->morphMany(Contact::class, 'object');
    }

    /**
     * @param \Illuminate\Support\Collection|array<\App\Models\Contact> $contacts
     */
    public function setContactsAttribute(Collection|array $contacts): void {
        $this->syncMorphMany('contacts', $contacts);
        $this->contacts_count = count($contacts);
    }

    public function headquarter(): MorphOne {
        $type = app()->make(Repository::class)->get('ep.headquarter_type');

        return $this
            ->morphOne(Location::class, 'object')
            ->whereHas('types', static function ($query) use ($type) {
                return $query->whereKey($type);
            });
    }
}
