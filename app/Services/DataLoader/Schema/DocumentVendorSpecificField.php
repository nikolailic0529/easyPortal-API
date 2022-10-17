<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class DocumentVendorSpecificField extends Type {
    public string $vendor;
    public ?string $sar;
    public ?string $ampId;
    public ?string $said;
    public ?string $groupId;
    public ?string $groupDescription;
}
