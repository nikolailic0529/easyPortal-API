<?php declare(strict_types = 1);

namespace App\Services\App\Utils;

use Illuminate\Support\Composer as IlluminateComposer;

use function array_merge;

class Composer extends IlluminateComposer {
    public function setVersion(string $version): int {
        $command = array_merge($this->findComposer(), ['config', 'version', $version]);
        $result  = $this->getProcess($command)->run();

        return $result;
    }
}
