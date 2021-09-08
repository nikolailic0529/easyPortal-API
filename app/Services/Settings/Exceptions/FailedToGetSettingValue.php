<?php declare(strict_types = 1);

namespace App\Services\Settings\Exceptions;

use App\Services\Settings\ServiceException;
use App\Services\Settings\Setting;
use Throwable;

use function sprintf;

class FailedToGetSettingValue extends ServiceException {
    public function __construct(
        protected Setting $setting,
        Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Impossible to get current value for setting `%s`.', $this->setting->getName()),
            $previous,
        );
    }
}
