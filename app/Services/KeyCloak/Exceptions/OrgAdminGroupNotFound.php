<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Services\KeyCloak\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

class OrgAdminGroupNotFound extends ServiceException {
    public function __construct(string $id, Throwable $previous = null) {
        parent::__construct("Org Admin Group `{$id}` not found.", $previous);

        $this->setLevel(LogLevel::WARNING);
    }
}
