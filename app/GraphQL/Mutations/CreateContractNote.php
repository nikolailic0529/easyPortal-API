<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;

use function array_map;

class CreateContractNote {
    public function __construct(
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
        protected CreateQuoteNote $createQuoteNote,
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
        $note                  = new Note();
        $note->user            = $this->auth->user();
        $note->document_id     = $args['input']['contract_id'];
        $note->organization_id = $this->organization->get()->getKey();
        $note->note            = $args['input']['note'];
        $note->files           = array_map(function ($file) use ($note) {
            return $this->createQuoteNote->createFile($note, $file);
        }, $args['input']['files']);
        $note->save();
        return ['created' => $note];
    }
}
