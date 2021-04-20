<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class SettingsFailedToLoadEnv extends SettingsException implements TranslatedException {
    public function __construct(
        protected string $file,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Failed to load ENV from `{$this->file}`",
            0,
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('settings.errors.failed_to_load_env', [
            'file' => $this->file,
        ]);
    }
}
