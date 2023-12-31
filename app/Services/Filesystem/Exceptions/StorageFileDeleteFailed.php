<?php declare(strict_types = 1);

namespace App\Services\Filesystem\Exceptions;

use App\Services\Filesystem\Disk;
use Throwable;

use function trans;

class StorageFileDeleteFailed extends StorageException {
    public function __construct(
        protected Disk $disc,
        protected string $path,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Delete failed: `{$path}` (disk: `{$disc}`)",
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return trans('errors.storage.file_delete_failed', [
            'disc' => (string) $this->disc,
            'file' => $this->path,
        ]);
    }
}
