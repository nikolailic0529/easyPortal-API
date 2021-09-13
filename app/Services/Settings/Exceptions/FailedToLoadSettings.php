<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\Settings\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

use function __;
use function sprintf;

class FailedToLoadSettings extends ServiceException implements TranslatedException {
    public function __construct(
        protected string $path,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Failed to load custom settings from `%s`.', $this->path),
            $previous,
        );

        $this->setLevel(LogLevel::EMERGENCY);
    }

    public function getErrorMessage(): string {
        return __('settings.errors.failed_to_load_settings');
    }
}
