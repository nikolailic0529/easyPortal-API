<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use App\Services\Filesystem\Exceptions\StorageException;
use App\Services\Filesystem\Exceptions\StorageFileCorrupted;
use App\Services\Filesystem\Exceptions\StorageFileDeleteFailed;
use App\Services\Filesystem\Exceptions\StorageFileSaveFailed;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\Date;

use function is_int;
use function json_decode;
use function json_encode;

use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class JsonStorage {
    protected function __construct(
        protected Disk $disc,
        protected string $file,
    ) {
        // empty
    }

    protected function getDisk(): Disk {
        return $this->disc;
    }

    protected function getFile(): string {
        return $this->file;
    }

    /**
     * @return array<mixed>
     */
    public function load(): array {
        $fs   = $this->getDisk()->filesystem();
        $data = [];

        try {
            if ($fs->exists($this->getFile())) {
                $data = (array) json_decode($fs->get($this->getFile()), true, flags: JSON_THROW_ON_ERROR);
            }
        } catch (Exception $exception) {
            throw new StorageFileCorrupted($this->getDisk(), $this->getFile(), $exception);
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     */
    public function save(array $data): bool {
        // Is file valid?
        if (!$this->validate()) {
            return false;
        }

        // Update
        try {
            $fs      = $this->getDisk()->filesystem();
            $success = $fs->put($this->getFile(), json_encode(
                $data,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_LINE_TERMINATORS
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR,
            ));

            if (!$success) {
                throw new StorageFileSaveFailed($this->getDisk(), $this->getFile());
            }
        } catch (StorageException) {
            // no action
        } catch (Exception $exception) {
            throw new StorageFileSaveFailed($this->getDisk(), $this->getFile(), $exception);
        }

        // Return
        return true;
    }

    public function delete(bool $force = false): bool {
        // Is file valid?
        if (!$force && !$this->validate()) {
            return false;
        }

        // Delete
        try {
            $fs      = $this->getDisk()->filesystem();
            $success = $fs->delete($this->getFile());

            if (!$success) {
                throw new StorageFileDeleteFailed($this->getDisk(), $this->getFile());
            }
        } catch (StorageException) {
            // no action
        } catch (Exception $exception) {
            throw new StorageFileDeleteFailed($this->getDisk(), $this->getFile(), $exception);
        }

        // Return
        return true;
    }

    public function getLastModified(): ?DateTimeInterface {
        $modified = null;

        try {
            $timestamp = $this->getDisk()->filesystem()->lastModified($this->getFile());

            if (is_int($timestamp)) {
                $modified = Date::createFromTimestamp($timestamp);
            }
        } catch (Exception) {
            // empty
        }

        return $modified;
    }

    protected function validate(): bool {
        $this->load();

        return true;
    }
}
