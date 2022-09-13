<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes as EloquentSoftDeletes;

/**
 * @mixin Model
 */
trait SoftDeletes {
    use EloquentSoftDeletes {
        restore as private eloquentRestore;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     * @noinspection  PhpMissingReturnTypeInspection
     *
     * @return bool
     */
    public function restore() {
        $result = $this->eloquentRestore();

        if (!$result) {
            throw new Exception('An unknown error occurred while restoring the model.');
        }

        return $result;
    }
}
