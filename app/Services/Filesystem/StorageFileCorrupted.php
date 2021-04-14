<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Throwable;

use function __;

class StorageFileCorrupted extends StorageException {
    public function __construct(Disk $disc, string $file, Throwable $previous = null) {
        parent::__construct(
            __('errors.storage.file_corrupted', [
                'disc' => $disc,
                'file' => $file,
            ]),
            0,
            $previous,
        );
    }
}
