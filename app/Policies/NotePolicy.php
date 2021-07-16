<?php declare(strict_types = 1);

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotePolicy {
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct() {
        // empty
    }

    /**
     * Determine if the given note can be updated by the user.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note  $note
     * @return bool
     */
    public function update(User $user, Note $note) {
        return $user->id === $note->user_id;
    }

    /**
     * Determine if the given note can be deleted by the user.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Note  $note
     * @return bool
     */
    public function delete(User $user, Note $note) {
        return $user->id === $note->user_id;
    }
}
