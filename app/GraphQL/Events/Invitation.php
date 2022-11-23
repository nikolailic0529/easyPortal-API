<?php declare(strict_types = 1);

namespace App\GraphQL\Events;

use App\Models\Invitation as InvitationModel;

abstract class Invitation {
    public function __construct(
        protected InvitationModel $invitation,
    ) {
        // empty
    }

    public function getInvitation(): InvitationModel {
        return $this->invitation;
    }
}
