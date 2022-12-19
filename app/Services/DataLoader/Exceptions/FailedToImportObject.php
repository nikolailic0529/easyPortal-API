<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Processors\Importer\ModelObject;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithKey;
use Throwable;

use function sprintf;

final class FailedToImportObject extends FailedToProcessObject implements GenericException {
    /**
     * @param (Type&TypeWithKey)|ModelObject $object
     */
    public function __construct(
        protected Importer $importer,
        protected Type $object,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Failed to import %s `%s`.',
                $this->object->getName(),
                $this->object->getKey(),
            ),
            $previous,
        );

        $this->setContext([
            'importer' => $this->importer::class,
            'object'   => $this->object,
        ]);
    }
}
