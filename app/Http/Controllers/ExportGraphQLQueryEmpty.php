<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class ExportGraphQLQueryEmpty extends Exception implements TranslatedException {
    use HasErrorCode;

    /**
     * @param array<mixed,string> $errors
     */
    public function __construct(Throwable $previous = null) {
        parent::__construct(
            'GraphQL query result is empty',
            0,
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('export.errors.graphql_query_empty');
    }
}
