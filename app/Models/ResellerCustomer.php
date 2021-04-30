<?php declare(strict_types = 1);

namespace App\Models;

/**
 * Reseller Customer.
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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer whereResellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer whereUpdatedAt($value)
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
