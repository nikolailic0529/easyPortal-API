<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Notifications;

use App\Models\User;
use App\Services\I18n\Formatter;
use App\Services\Maintenance\Settings;
use App\Services\Notificator\Notification;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Notifications\Action;
use Illuminate\Support\Facades\Date;

use function array_merge;

class Scheduled extends Notification {
    public function __construct(
        protected Settings $settings,
    ) {
        parent::__construct();
    }

    public function shouldSend(User $notifiable, string $channel): bool {
        return $this->settings->end && Date::now() < $this->settings->end;
    }

    protected function getMailAction(User $notifiable, Repository $config, Formatter $formatter): ?Action {
        return null;
    }

    /**
     * @inheritDoc
     */
    protected function getMailReplacements(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
    ): array {
        return array_merge(parent::getMailReplacements($notifiable, $config, $formatter, $translate), [
            'message' => $this->settings->message ?: $translate('default.message'),
        ]);
    }
}
