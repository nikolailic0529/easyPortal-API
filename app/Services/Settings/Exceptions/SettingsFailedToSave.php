<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\TranslatedException;
use Throwable;

use function __;

class SettingsFailedToSave extends SettingsException implements TranslatedException {
    public function __construct(Throwable $previous = null) {
        parent::__construct(__('errors.services.settings.failed_to_save'), 0, $previous);
    }
}
