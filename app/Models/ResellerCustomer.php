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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomernewQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerquery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerwhereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerwhereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerwhereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerwhereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerwhereResellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomerwhereUpdatedAt($value)
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
