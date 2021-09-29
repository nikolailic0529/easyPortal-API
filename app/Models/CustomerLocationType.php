<?php declare(strict_types = 1);

namespace App\Models;

/**
 * CustomerLocationType.
 *
 * @property string                       $id
 * @property string                       $customer_location_id
 * @property string                       $type_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerLocationType query()
 * @mixin \Eloquent
 */
class CustomerLocationType extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_location_types';
}
