<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\HttpException;
use Throwable;

use function __;

class ExportGraphQLQueryEmpty extends HttpException {
    /**
     * @param array<mixed,string> $errors
     */
    public function __construct(Throwable $previous = null) {
        parent::__construct('GraphQL query result is empty', $previous);
    }

    public function getErrorMessage(): string {
        return __('export.errors.graphql_query_empty');
    }
}
