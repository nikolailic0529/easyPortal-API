<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\TranslatedException;
use App\Services\Settings\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

use function __;

class FailedToLoadConfig extends ServiceException implements TranslatedException {
    public function __construct(
        Throwable $previous = null,
    ) {
        parent::__construct(
            'Failed to load custom config file.',
            $previous,
        );

        $this->setLevel(LogLevel::EMERGENCY);
    }

    public function getErrorMessage(): string {
        return __('settings.errors.failed_to_load_config');
    }
}
