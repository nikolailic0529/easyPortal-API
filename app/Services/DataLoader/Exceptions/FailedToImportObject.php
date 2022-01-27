<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\DataLoader\Importers\Importer;
use App\Services\DataLoader\Schema\Type;
use Throwable;

final class FailedToImportObject extends FailedToProcessObject implements GenericException {
    public function __construct(
        protected Importer $importer,
        protected Type $object,
        Throwable $previous = null,
    ) {
        parent::__construct('Failed to import object.', $previous);

        $this->setContext([
            'importer' => $this->importer::class,
            'object'   => $this->object,
        ]);
    }
}
