<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\GraphQLException;
use Throwable;

use function __;

class ImportOemsImportFailed extends GraphQLException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Failed to import OEMs.', $previous);
    }

    public function getErrorMessage(): string {
        return __('graphql.mutations.importOems.import_failed');
    }
}
