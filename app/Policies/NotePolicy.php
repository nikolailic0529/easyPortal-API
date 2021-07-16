<?php declare(strict_types = 1);

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotePolicy {
    use HandlesAuthorization;

    public function contractsView(User $user, Note $note): bool {
        return $user->getKey() === $note->user_id;
    }

    public function quotesView(User $user, Note $note): bool {
        return $user->getKey() === $note->user_id;
    }

    public function customersView(User $user, Note $note): bool {
        return $user->getKey() === $note->user_id;
    }

    public function orgAdminister(): bool {
        return true;
    }
}
