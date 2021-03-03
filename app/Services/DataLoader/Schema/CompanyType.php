<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

/**
 * @internal
 */
class CompanyType extends Type {
    public string $vendorSpecificId;
    public string $vendor;
    public string $type;
    public string $status;
}
