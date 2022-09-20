<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Processors\Importer\ModelObject;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\TypeWithId;
use Throwable;

use function sprintf;

final class FailedToImportObject extends FailedToProcessObject implements GenericException {
    public function __construct(
        protected Importer $importer,
        protected Type $object,
        Throwable $previous = null,
    ) {
        $id    = null;
        $class = $this->object instanceof ModelObject
            ? $this->object->model->getMorphClass()
            : $this->object->getName();

        if ($this->object instanceof ModelObject) {
            $id = $this->object->model->getKey();
        } elseif ($this->object instanceof TypeWithId) {
            $id = $this->object->id;
        } else {
            // empty
        }

        parent::__construct(
            sprintf(
                'Failed to import %s `%s`.',
                $class,
                $id ?? '<unknown>',
            ),
            $previous,
        );

        $this->setContext([
            'importer' => $this->importer::class,
            'object'   => $this->object,
        ]);
    }
}
