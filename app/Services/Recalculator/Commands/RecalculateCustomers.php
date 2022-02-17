<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Jobs\CustomersRecalculate;
use Illuminate\Contracts\Config\Repository;

class RecalculateCustomers extends Recalculate {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:recalculator-recalculate-customers',
            '${objects}' => 'customers',
        ];
    }

    public function handle(Repository $config, CustomersRecalculate $job): int {
        return $this->process($config, $job);
    }
}
