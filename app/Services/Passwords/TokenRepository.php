<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use App\Models\PasswordReset;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Date;
use LogicException;

/**
 * Implementation very close to {@link \Illuminate\Auth\Passwords\DatabaseTokenRepository}
 * but uses Eloquent instead of Query Builder. This is required to avoid
 * deleting records from database.
 */
class TokenRepository extends DatabaseTokenRepository implements TokenRepositoryInterface {
    // <editor-fold desc="TokenRepositoryInterface">
    // =========================================================================
    public function create(CanResetPassword $user): string {
        $email = $user->getEmailForPasswordReset();

        $this->deleteExisting($user);

        // We will create a new, random token for the user so that we can e-mail them
        // a safe link to the password reset form. Then we will insert a record in
        // the database so that we can verify the token within the actual reset.
        $token = $this->createNewToken();

        (new PasswordReset())
            ->forceFill($this->getPayload($email, $token))
            ->save();

        return $token;
    }

    /**
     * @inheritDoc
     */
    public function exists(CanResetPassword $user, $token): bool {
        $reset = PasswordReset::query()
            ->where('email', '=', $user->getEmailForPasswordReset())
            ->first();

        return $reset
            && !$this->tokenExpired($reset->created_at)
            && $this->hasher->check($token, $reset->token);
    }

    public function recentlyCreatedToken(CanResetPassword $user): bool {
        $reset = PasswordReset::query()
            ->where('email', '=', $user->getEmailForPasswordReset())
            ->first();

        return $reset
            && $this->tokenRecentlyCreated($reset->created_at);
    }

    public function deleteExpired(): void {
        $expiredAt = Date::now()->subSeconds($this->expires);
        $query     = PasswordReset::query()
            ->where('created_at', '<', $expiredAt);

        foreach ($query->changeSafeIterator() as $reset) {
            $reset->delete();
        }
    }
    // </editor-fold>

    // <editor-fold desc="DatabaseTokenRepository">
    // =========================================================================
    protected function deleteExisting(CanResetPassword $user): int {
        $count = 0;
        $query = PasswordReset::query()
            ->where('email', '=', $user->getEmailForPasswordReset());

        foreach ($query->changeSafeIterator() as $reset) {
            $reset->delete();
            $count++;
        }

        return $count;
    }

    protected function getTable(): Builder {
        throw new LogicException('This method should not be used.');
    }
    // </editor-fold>
}
