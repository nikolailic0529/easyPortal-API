<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Illuminate\Auth\AuthManager;

class DeleteContractNote {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $user = $this->auth->user();
        $note = $user->notes()->whereKey($args['input']['id'])->first();

        if ($note) {
            $note->delete();
        }

        return ['deleted' => (bool) $note];
    }
}
