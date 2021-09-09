<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Exceptions;

use App\Models\Callbacks\GetKey;
use App\Services\DataLoader\Resolver;
use Illuminate\Support\Collection;
use Throwable;

use function sprintf;

class FailedToUpdateCalculatedProperties extends FailedToProcessObject {
    /**
     * @param \Illuminate\Support\Collection<\App\Models\Model> $objects
     */
    public function __construct(
        protected Resolver $resolver,
        protected Collection $objects,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Failed to update calculated properties for `%s`.',
            $this->resolver::class,
        ), $previous);

        $this->setContext([
            'resolver' => $this->resolver::class,
            'objects'  => $this->objects->map(new GetKey()),
        ]);
    }
}
