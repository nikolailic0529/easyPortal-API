<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Exceptions;

use App\Services\KeyCloak\Importer\UsersImporter;
use App\Services\KeyCloak\ServiceException;
use Throwable;

class FailedToImportObject extends ServiceException {
    public function __construct(
        protected UsersImporter $importer,
        protected object $object,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to import object.', $previous);

        $this->setContext([
            'importer' => $this->importer::class,
            'object'   => $this->object,
        ]);
    }
}
