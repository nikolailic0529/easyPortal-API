<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\Contracts\TranslatedException;
use App\Services\Settings\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

use function sprintf;
use function trans;

class FailedToSaveSettings extends ServiceException implements TranslatedException {
    public function __construct(
        protected string $path,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Failed to save custom settings to `%s`.', $this->path),
            $previous,
        );

        $this->setLevel(LogLevel::EMERGENCY);
    }

    public function getErrorMessage(): string {
        return trans('settings.errors.failed_to_save_settings');
    }
}
