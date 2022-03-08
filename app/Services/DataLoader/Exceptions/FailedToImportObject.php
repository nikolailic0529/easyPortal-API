<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Exceptions\Contracts\GenericException;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Schema\Type;
use ReflectionClass;
use Throwable;

use function sprintf;

final class FailedToImportObject extends FailedToProcessObject implements GenericException {
    public function __construct(
        protected Importer $importer,
        protected Type $object,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Failed to import %s `%s`.',
                (new ReflectionClass($this->object))->getShortName(),
                $this->object->id ?? '<unknown>',
            ),
            $previous,
        );

        $this->setContext([
            'importer' => $this->importer::class,
            'object'   => $this->object,
        ]);
    }
}
