<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Jobs\CustomersRecalculate;
use Illuminate\Contracts\Config\Repository;

class RecalculateCustomers extends Recalculate {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:data-loader-recalculate-customers',
            '${objects}' => 'customers',
        ];
    }

    public function handle(Repository $config, CustomersRecalculate $job): int {
        return $this->process($config, $job);
    }
}
