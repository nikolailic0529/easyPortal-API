<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use Illuminate\Contracts\Auth\Access\Gate;
use Nuwave\Lighthouse\Exceptions\AuthorizationException;

class DeleteContractNote {
    public function __construct(
        protected Gate $gate,
    ) {
        // empty
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        return [
            'deleted' => $this->deleteNote(
                $args['input']['id'],
                ['org-administer', 'contracts-view'],
            ),
        ];
    }

    /**
     *
     * @param array<string> $permissions
     */
    public function deleteNote(string $noteId, array $permissions): bool {
        $note = Note::query()->whereKey($noteId)->first();

        if (!$note || $note->note === null || !$this->gate->any($permissions, [$note])) {
            throw new AuthorizationException();
        }

        return $note->delete();
    }
}
