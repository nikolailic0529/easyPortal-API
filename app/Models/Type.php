<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Type.
 *
 * @property string                                                         $id
 * @property string                                                         $object_type
 * @property string                                                         $key
 * @property string                                                         $name
 * @property \Carbon\CarbonImmutable                                        $created_at
 * @property \Carbon\CarbonImmutable                                        $updated_at
 * @property \Carbon\CarbonImmutable|null                                   $deleted_at
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Location> $locations
 * @property-read int|null                                                  $locations_count
 * @property \Illuminate\Database\Eloquent\Collection<\App\Models\Contact>  $contacts
 * @property-read int|null                                                  $contacts_count
 * @method static \Database\Factories\TypeFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereObjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Type whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Type extends PolymorphicModel {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'types';

    public function locations(): BelongsToMany {
        return $this->belongsToMany(Location::class, 'location_types')->withTimestamps();
    }

    public function contacts(): BelongsToMany {
        return $this->belongsToMany(Contact::class, 'contact_types')->withTimestamps();
    }
}
