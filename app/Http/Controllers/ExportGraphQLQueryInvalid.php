<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Exceptions\HasErrorCode;
use App\Exceptions\TranslatedException;
use Exception;
use Throwable;

use function __;

class ExportGraphQLQueryInvalid extends Exception implements TranslatedException {
    use HasErrorCode;

    public function __construct(array $errors, Throwable $previous = null) {
        parent::__construct(
            'GraphQL query invalid: '.json_encode($errors).')',
            0,
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('export.errors.graphql_query_invalid');
    }
}
