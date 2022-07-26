<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Auth;

use App\Models\Invitation as InvitationModel;
use App\Models\Organization;
use App\Services\Organization\Eloquent\OwnedByScope;
use App\Utils\Eloquent\GlobalScopes\GlobalScopes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

use function is_array;

class Invitation {
    public function __construct(
        protected Encrypter $encrypter,
    ) {
        // empty
    }

    /**
     * @param array{token: string} $args
     */
    public function __invoke(mixed $root, array $args): ?InvitationModel {
        return $this->getInvitation($args['token']);
    }

    public function org(InvitationModel $invitation): ?Organization {
        return $invitation->organization;
    }

    public function isUsed(InvitationModel $invitation): bool {
        return $invitation->used_at !== null;
    }

    public function isExpired(InvitationModel $invitation): bool {
        return $invitation->expired_at->isPast();
    }

    public function isOutdated(InvitationModel $invitation): bool {
        $last     = GlobalScopes::callWithout(
            OwnedByScope::class,
            static function () use ($invitation): ?InvitationModel {
                return InvitationModel::query()
                    ->where('organization_id', '=', $invitation->organization_id)
                    ->where('user_id', '=', $invitation->user_id)
                    ->orderByDesc('created_at')
                    ->first();
            },
        );
        $outdated = !$invitation->is($last);

        return $outdated;
    }

    public function getInvitation(string $token): ?InvitationModel {
        // Id
        $id = null;

        try {
            $decrypted = $this->encrypter->decrypt($token);
            $id        = is_array($decrypted) && isset($decrypted['invitation'])
                ? $decrypted['invitation']
                : null;
        } catch (DecryptException) {
            // empty
        }

        if (!$id) {
            return null;
        }

        // Invitation
        return GlobalScopes::callWithout(
            OwnedByScope::class,
            static function () use ($id): ?InvitationModel {
                return InvitationModel::query()->whereKey($id)->first();
            },
        );
    }
}
