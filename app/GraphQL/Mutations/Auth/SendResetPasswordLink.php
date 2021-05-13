<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\KeyCloak\UserProvider;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Contracts\Auth\PasswordBroker;

class SendResetPasswordLink {
    public function __construct(
        protected PasswordBrokerManager $manager,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $_, array $args): array {
        $result = $this->manager->broker()->sendResetLink([
            UserProvider::CREDENTIAL_EMAIL => $args['input']['email'],
        ]);
        $result = $result === PasswordBroker::RESET_LINK_SENT;

        return [
            'result' => $result,
        ];
    }
}
