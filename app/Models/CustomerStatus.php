<?php declare(strict_types = 1);

namespace App\Models;

/**
 * CustomerStatus.
 *
 * @property string                       $id
 * @property string                       $customer_id
 * @property string                       $status_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\CustomerStatus query()
 * @mixin \Eloquent
 */
class CustomerStatus extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'customer_statuses';
}
