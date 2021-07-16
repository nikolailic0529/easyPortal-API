<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;


class DeleteQuoteNote {
    public function __construct(
        protected DeleteContractNote $deleteContractNote,
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
            'deleted' => $this->deleteContractNote->deleteNote(
                $args['input']['id'],
                ['org-administer', 'quotes-view', 'customers-view'],
            ),
        ];
    }
}
