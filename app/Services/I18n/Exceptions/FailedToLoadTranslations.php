<?php declare(strict_types = 1);

namespace App\Services\I18n\Exceptions;

use App\Services\I18n\ServiceException;
use Psr\Log\LogLevel;
use Throwable;

class FailedToLoadTranslations extends ServiceException {
    public function __construct(
        protected string $locale,
        Throwable $previous = null,
    ) {
        parent::__construct(
            "Failed to load custom translation file for Locale `{$this->locale}`.",
            $previous,
        );

        $this->setLevel(LogLevel::CRITICAL);
    }
}
