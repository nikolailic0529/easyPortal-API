<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Exceptions\HasErrorCode;
use Exception;

abstract class SettingsException extends Exception {
    use HasErrorCode;
}
