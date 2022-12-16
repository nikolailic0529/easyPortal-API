<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\GraphQL\Events\InvitationAccepted;
use App\GraphQL\Events\InvitationCreated;
use App\GraphQL\Events\InvitationExpired;
use App\GraphQL\Events\InvitationOutdated;
use App\GraphQL\Events\InvitationUsed;
use App\Services\Audit\Enums\Action;
use LogicException;

class InvitationListener extends Listener {
    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            InvitationCreated::class,
            InvitationAccepted::class,
            InvitationOutdated::class,
            InvitationExpired::class,
            InvitationUsed::class,
        ];
    }

    public function __invoke(object $event): void {
        $org    = null;
        $model  = null;
        $action = null;

        if ($event instanceof InvitationCreated) {
            $org    = $event->getInvitation()->organization_id;
            $model  = $event->getInvitation();
            $action = Action::invitationCreated();
        } elseif ($event instanceof InvitationAccepted) {
            $org    = $event->getInvitation()->organization_id;
            $model  = $event->getInvitation();
            $action = Action::invitationAccepted();
        } elseif ($event instanceof InvitationOutdated) {
            $org    = $event->getInvitation()->organization_id;
            $model  = $event->getInvitation();
            $action = Action::invitationOutdated();
        } elseif ($event instanceof InvitationExpired) {
            $org    = $event->getInvitation()->organization_id;
            $model  = $event->getInvitation();
            $action = Action::invitationExpired();
        } elseif ($event instanceof InvitationUsed) {
            $org    = $event->getInvitation()->organization_id;
            $model  = $event->getInvitation();
            $action = Action::invitationUsed();
        } else {
            throw new LogicException('Unknown event O_O');
        }

        $this->auditor->create($org, $action, $model);
    }
}
