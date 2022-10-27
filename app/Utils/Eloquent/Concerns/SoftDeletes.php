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
        runSoftDelete as private eloquentRunSoftDelete;
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

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     * @noinspection  PhpMissingReturnTypeInspection
     *
     * @return void
     */
    protected function runSoftDelete() {
        // Laravel will update `deleted_at` even if the model is already trashed.
        // This is strange unwanted behavior that also creates excess queries
        // while DataLoader sync data.
        if (!$this->trashed()) {
            $this->eloquentRunSoftDelete();
        }
    }
}
