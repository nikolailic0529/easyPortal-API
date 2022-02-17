<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Commands;

use App\Services\Recalculator\Jobs\ResellersRecalculate;
use Illuminate\Contracts\Config\Repository;

class RecalculateResellers extends Recalculate {
    /**
     * @inheritDoc
     */
    protected function getReplacements(): array {
        return [
            '${command}' => 'ep:recalculator-recalculate-resellers',
            '${objects}' => 'resellers',
        ];
    }

    public function handle(Repository $config, ResellersRecalculate $job): int {
        return $this->process($config, $job);
    }
}
