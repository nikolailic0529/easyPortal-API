<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use App\Services\Maintenance\Maintenance as MaintenanceService;
use App\Services\Maintenance\Settings;

class Maintenance {
    public function __construct(
        protected MaintenanceService $maintenance,
    ) {
        // empty
    }

    public function __invoke(): ?Settings {
        return $this->maintenance->getSettings();
    }
}
