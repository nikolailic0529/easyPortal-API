<?php declare(strict_types = 1);

namespace App\Notifications;

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Services\I18n\Formatter;
use App\Services\Notificator\Notification;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Notifications\Action;

use function array_merge;

class OrganizationUserInvitation extends Notification {
    public function __construct(
        protected Invitation $invitation,
        protected string $url,
    ) {
        parent::__construct();
    }

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
        return new Action(
            $translate('action', $replacements),
            $this->url,
        );
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
            'organizationName' => $this->invitation->organization->name,
        ]);
    }
}
