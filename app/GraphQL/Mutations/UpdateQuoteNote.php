<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

class UpdateQuoteNote {
    public function __construct(
        protected UpdateContractNote $updateContractNote,
    ) {
        // empty
    }
    /**
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        return [
            'updated' => $this->updateContractNote->updateNote(
                $args['input']['id'],
                ['quotes-view'],
                $args['input']['note'] ?? null,
                $args['input']['pinned'] ?? null,
                $args['input']['files'] ?? null,
            ),
        ];
    }
}
