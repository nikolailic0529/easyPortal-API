<?php declare(strict_types = 1);

namespace Tests\GraphQL;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Json\JsonMatchesFragment;

use function array_merge;
use function trans;

/**
 * @phpstan-import-type ValidationErrors from GraphQLValidationErrorsContent
 */
class GraphQLValidationError extends GraphQLError {
    /**
     * @template T
     *
     * @param ValidationErrors|Closure(T):ValidationErrors $validationErrors
     */
    public function __construct(
        string $root,
        protected Closure|array $validationErrors,
    ) {
        parent::__construct($root, static function (): array {
            return [
                trans('errors.validation_failed'),
            ];
        });
    }

    protected function getResponseClass(): string {
        return GraphQLError::class;
    }

    /**
     * @inheritdoc
     */
    protected function getResponseConstraints(): array {
        return array_merge(parent::getResponseConstraints(), [
            new JsonMatchesFragment(
                'errors.0.extensions.validation',
                new GraphQLValidationErrorsContent($this->validationErrors),
            ),
        ]);
    }
}
