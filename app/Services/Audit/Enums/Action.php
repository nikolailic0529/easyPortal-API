<?php declare(strict_types = 1);

namespace App\Services\Audit\Enums;

use LastDragon_ru\LaraASP\Eloquent\Enum;

class Action extends Enum {
    // <editor-fold desc="Models">
    // =========================================================================
    public static function modelCreated(): static {
        return static::make('model.created');
    }

    public static function modelUpdated(): static {
        return static::make('model.updated');
    }

    public static function modelDeleted(): static {
        return static::make('model.deleted');
    }

    public static function modelRestored(): static {
        return static::make('model.restored');
    }
    // </editor-fold>

    // <editor-fold desc="Auth">
    // =========================================================================
    public static function authSignedIn(): static {
        return static::make('auth.signedIn');
    }

    public static function authSignedOut(): static {
        return static::make('auth.signedOut');
    }

    public static function authFailed(): static {
        return static::make('auth.failed');
    }

    public static function authPasswordReset(): static {
        return static::make('auth.passwordReset');
    }
    // </editor-fold>

    // <editor-fold desc="Export">
    // =========================================================================
    public static function exported(): static {
        return static::make('exported');
    }
    // </editor-fold>

    // <editor-fold desc="Invitation">
    // =========================================================================
    public static function invitationCreated(): static {
        return static::make('invitation.created');
    }

    public static function invitationAccepted(): static {
        return static::make('invitation.accepted');
    }

    public static function invitationOutdated(): static {
        return static::make('invitation.outdated');
    }

    public static function invitationExpired(): static {
        return static::make('invitation.expired');
    }

    public static function invitationUsed(): static {
        return static::make('invitation.used');
    }
    // </editor-fold>

    // <editor-fold desc="Org">
    // =========================================================================
    /**
     * If the `object` is not `null` it is means that the User changed organization
     * to `object`. If the `object` is `null` the User switched organization
     * to another.
     */
    public static function orgChanged(): static {
        return static::make('org.changed');
    }
    // </editor-fold>
}
