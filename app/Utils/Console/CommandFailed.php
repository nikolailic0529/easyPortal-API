<?php declare(strict_types = 1);

namespace App\Utils\Console;

use App\Exceptions\ApplicationException;
use Throwable;

use function sprintf;

class CommandFailed extends ApplicationException {
    public function __construct(object $command, int $status, Throwable $previous = null) {
        parent::__construct(sprintf(
            'Command `%s` failed with status code `%s`.',
            $command::class,
            $status,
        ), $previous);
    }
}
