<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\Settings\ServiceException;
use Throwable;

use function __;

class FailedToLoadEnv extends ServiceException implements TranslatedException {
    public function __construct(
        protected string $path,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Failed to load ENV from `{$this->path}`",
            $previous,
        );
    }

    public function getErrorMessage(): string {
        return __('settings.errors.failed_to_load_env', [
            'file' => $this->path,
        ]);
    }
}
