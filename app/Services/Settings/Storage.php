<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Exceptions\FailedToLoadSettings;
use App\Services\Settings\Exceptions\FailedToSaveSettings;
use Exception;
use Illuminate\Contracts\Foundation\Application;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Storage {
    public function __construct(
        protected Application $app,
    ) {
        // empty
    }

    /**
     * @return array<string, mixed>
     */
    public function load(): array {
        $path = $this->getPath();
        $data = [];

        try {
            if (is_file($path)) {
                $data = (array) json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
            }
        } catch (Exception $exception) {
            throw new FailedToLoadSettings($path, $exception);
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     */
    public function save(array $data): bool {
        $path = $this->getPath();

        try {
            $success = file_put_contents($path, json_encode(
                $data,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_LINE_TERMINATORS
                | JSON_THROW_ON_ERROR,
            ));

            if ($success === false) {
                throw new FailedToSaveSettings($path);
            }
        } catch (FailedToSaveSettings) {
            // no action
        } catch (Exception $exception) {
            throw new FailedToSaveSettings($path, $exception);
        }

        // Return
        return true;
    }

    protected function getPath(): string {
        return $this->app->storagePath().'/'.Settings::PATH;
    }
}
