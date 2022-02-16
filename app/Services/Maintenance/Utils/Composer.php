<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Utils;

use Closure;
use Illuminate\Support\Composer as IlluminateComposer;

use function array_merge;

class Composer extends IlluminateComposer {
    /**
     * @param \Closure(string $stderr): void|null $onFail
     */
    public function setVersion(string $version, Closure $onFail = null): int {
        $command = array_merge($this->findComposer(), ['config', 'version', $version]);
        $process = $this->getProcess($command);
        $result  = $process->run();

        if (!$process->isSuccessful() && $onFail !== null) {
            $onFail($process->getErrorOutput());
        }

        return $result;
    }
}
