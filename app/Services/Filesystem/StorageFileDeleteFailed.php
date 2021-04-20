<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use App\Exceptions\ErrorCodes;
use Throwable;

use function __;

class StorageFileDeleteFailed extends StorageException {
    public function __construct(
        protected Disk $disc,
        protected string $file,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Delete failed: `{$file}` (disk: `{$disc}`)",
            0,
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('errors.storage.file_delete_failed', [
            'disc' => $this->disc,
            'file' => $this->file,
        ]);
    }
}
