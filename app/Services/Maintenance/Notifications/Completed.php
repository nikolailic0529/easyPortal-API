<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Notifications;

use App\Models\User;
use App\Services\I18n\Formatter;
use App\Services\Notificator\Actions\GoToApplication;
use App\Services\Notificator\NotificationQueued;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Notifications\Action;

class Completed extends NotificationQueued {
    /**
     * @inheritDoc
     */
    protected function getMailAction(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
        array $replacements,
    ): ?Action {
        return new GoToApplication($config);
    }
}
