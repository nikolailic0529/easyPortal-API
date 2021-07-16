<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use Illuminate\Auth\AuthManager;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

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
        return [
            'deleted' => $this->deleteNote(
                $args['input']['id'],
                ['org-administer', 'contracts-view', 'customers-view'],
            ),
        ];
    }

    /**
     *
     * @param array<string> $permissions
     */
    public function deleteNote(string $noteId, array $permissions): bool {
        $note = Note::whereKey($noteId)->first();

        if (!$this->auth->user()->canAny($permissions, [$note])) {
            throw new AuthorizationException();
        }

        if ($note) {
            $note->delete();
        }

        return (bool) $note;
    }
}
