<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\HttpException;
use Throwable;

use function __;

class ExportGraphQLQueryInvalid extends HttpException {
    /**
     * @param array<mixed> $errors
     */
    public function __construct(
        protected array $errors,
        Throwable $previous = null,
    ) {
        parent::__construct('GraphQL query invalid.', $previous);

        $this->setContext([
            'errors' => $this->errors,
        ]);
    }

    public function getErrorMessage(): string {
        return __('export.errors.graphql_query_invalid');
    }
}
