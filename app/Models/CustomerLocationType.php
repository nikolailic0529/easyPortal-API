<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * CustomerLocationType.
 *
 * @property string                       $id
 * @property string                       $type
 * @property string                       $name
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CustomerLocationType extends Model {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_location_types';
}
