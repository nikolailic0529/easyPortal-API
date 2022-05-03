<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Exceptions;

use App\Models\User;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Throwable;

use function sprintf;

class FailedToImportUserConflictType extends FailedToImport {
    /**
     * @param Processor<mixed,mixed,State> $processor
     */
    public function __construct(Processor $processor, object $object, User $user, Throwable $previous = null) {
        parent::__construct(
            $processor,
            $object,
            sprintf(
                'Existing User has a different type (`%s`).',
                $user->type,
            ),
            $previous,
        );
    }
}
