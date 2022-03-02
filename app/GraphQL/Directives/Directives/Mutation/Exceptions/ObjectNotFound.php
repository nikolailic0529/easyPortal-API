<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Exceptions;

use App\GraphQL\Directives\Directives\Mutation\MutationException;
use Throwable;

use function __;
use function sprintf;

class ObjectNotFound extends MutationException {
    public function __construct(
        protected ?string $object,
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Object `%s` not found.',
            $this->object,
        ), $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.directives.@mutation.object_not_found', [
            'object' => $this->getObjectName(),
        ]);
    }

    protected function getObjectName(): string {
        $object     = $this->object ?: 'Object';
        $string     = "graphql.directives.@mutation.object.{$object}";
        $translated = __($string);

        return $string !== $translated
            ? $translated
            : $object;
    }
}
