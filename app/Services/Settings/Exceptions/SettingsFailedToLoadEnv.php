<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class SettingsFailedToLoadEnv extends SettingsException implements TranslatedException {
    public function __construct(string $file, Throwable $previous = null) {
        parent::__construct(
            __('settings.errors.failed_to_load_env', [
                'file' => $file,
            ]),
            0,
            $previous,
        );
    }
}
