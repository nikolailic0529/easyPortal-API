<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Models\User;
use App\Services\KeyCloak\UserProvider;
use App\Services\Passwords\PasswordBroker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;

class ResetPassword {
    public function __construct(
        protected PasswordBrokerFactory $password,
        protected Dispatcher $events,
        protected Hasher $hasher,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $_, array $args): array {
        $email    = $args['input']['email'];
        $password = $args['input']['password'];
        $result   = $this->password->broker()->reset(
            [
                UserProvider::CREDENTIAL_EMAIL => $email,
                'password'                     => $password,
                'token'                        => $args['input']['token'],
            ],
            function (User $user, string $password): void {
                $user->password = $this->hasher->make($password);
                $user->save();

                $this->events->dispatch(new PasswordReset($user));
            },
        );
        if ($result === PasswordBroker::INVALID_PASSWORD) {
            throw new ResetPasswordSamePasswordException();
        }

        $result = $result === PasswordBroker::PASSWORD_RESET;

        return [
            'result' => $result,
        ];
    }
}
