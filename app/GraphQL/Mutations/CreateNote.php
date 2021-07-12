<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use Illuminate\Auth\AuthManager;

class CreateNote {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $note              = new Note();
        $note->user        = $this->auth->user();
        $note->document_id = $args['input']['document_id'];
        $note->note        = $args['input']['note'];
        $note->save();
        return ['created' => $note];
    }
}
