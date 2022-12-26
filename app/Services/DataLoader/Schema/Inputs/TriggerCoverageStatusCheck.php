<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Inputs;

use App\Services\DataLoader\Schema\Input;

class TriggerCoverageStatusCheck extends Input {
    public ?string $assetId;
    public ?string $customerId;
    public ?string $type;
}
