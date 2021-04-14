<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Throwable;

use function __;

class StorageFileDeleteFailed extends StorageException {
    public function __construct(Disk $disc, string $file, Throwable $previous = null) {
        parent::__construct(
            __('errors.storage.file_delete_failed', [
                'disc' => $disc,
                'file' => $file,
            ]),
            0,
            $previous,
        );
    }
}
