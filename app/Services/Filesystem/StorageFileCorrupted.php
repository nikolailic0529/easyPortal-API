<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Throwable;

use function __;

class StorageFileCorrupted extends StorageException {
    public function __construct(
        protected Disk $disc,
        protected string $path,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "File corrupted: `{$path}` (disk: `{$disc}`)",
            0,
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('errors.storage.file_corrupted', [
            'disc' => $this->disc,
            'file' => $this->path,
        ]);
    }
}
