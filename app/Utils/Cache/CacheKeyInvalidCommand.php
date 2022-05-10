<?php declare(strict_types = 1);

namespace App\Utils\Cache;

use App\Exceptions\ApplicationException;
use Illuminate\Console\Command;
use Throwable;

use function sprintf;

class CacheKeyInvalidCommand extends ApplicationException {
    public function __construct(Command $command, Throwable $previous = null) {
        parent::__construct(sprintf(
            'The command `%s` does not have a name.',
            $command::class,
        ), $previous);
    }
}
