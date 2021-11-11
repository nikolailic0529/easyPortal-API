<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

class CoverageEntry extends Type {
    public string  $coverageStartDate;
    public string  $coverageEndDate;
    public string  $type;
    public string  $status;
    public ?string $description;
    public ?string $serviceSku;
}
