<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Exceptions;

use App\GraphQL\Directives\Directives\Mutation\MutationException;
use Nuwave\Lighthouse\Exceptions\ValidationException;
use Psr\Log\LogLevel;
use Throwable;

use function sprintf;

class ValidatorException extends MutationException {
    /**
     * @param array<mixed> $context
     */
    public function __construct(
        protected string $rule,
        array $context = [],
        Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Validation failed for rule `%s`.',
            $this->rule,
        ), $previous);

        $this->setLevel(LogLevel::NOTICE);
        $this->setContext($context);
    }

    public function isClientSafe(): bool {
        return true;
    }

    public function getCategory(): string {
        return ValidationException::CATEGORY;
    }

    public function getErrorMessage(): string {
        return $this->translate(null, "validation.rule.{$this->rule}", $this->getContext());
    }
}
