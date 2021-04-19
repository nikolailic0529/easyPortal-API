<?php declare(strict_types = 1);

namespace App\Models;

class ResellerCustomer extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_customers';
}
