<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\Queue\Progress;
use Illuminate\Contracts\Cache\Repository;

class ImporterProgress {
    public function __construct(
        protected ImporterCronJob $job,
    ) {
        // empty
    }

    public function __invoke(Repository $cache): ?Progress {
        $state    = $this->job->getState($cache);
        $progress = null;

        if ($state) {
            $progress = new Progress($state->total, $state->processed);
        }

        return $progress;
    }
}
