<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Oem\Hpe;

use App\GraphQL\GraphQLException;
use Throwable;

use function trans;

class ImportImportFailed extends GraphQLException {
    public function __construct(Throwable $previous = null) {
        parent::__construct('Failed to import OEMs.', $previous);
    }

    public function getErrorMessage(): string {
        return trans('graphql.mutations.oem.hpe.import.import_failed');
    }
}
