<?php declare(strict_types = 1);

namespace App\Models;

/**
 * Location Type (pivot)
 *
 * @property string                       $id
 * @property string                       $location_id
 * @property string                       $type_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType whereLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType whereTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class LocationType extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'location_types';
}
