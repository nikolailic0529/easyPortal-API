<?php declare(strict_types = 1);

namespace App\Models;

/**
 * ResellerCustomer.
 *
 * @property string                       $id
 * @property string                       $reseller_id
 * @property string                       $customer_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer query()
 * @mixin \Eloquent
 */
class ResellerCustomer extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_customers';
}
