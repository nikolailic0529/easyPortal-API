<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\Keycloak\Auth\UserProvider;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;

class SendResetPasswordLink {
    public function __construct(
        protected PasswordBrokerFactory $password,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $result = $this->password->broker()->sendResetLink([
            UserProvider::CREDENTIAL_EMAIL => $args['input']['email'],
        ]);
        $result = $result === PasswordBroker::RESET_LINK_SENT;

        return [
            'result' => $result,
        ];
    }
}
