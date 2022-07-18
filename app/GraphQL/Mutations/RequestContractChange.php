<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Mutations\Message\Create;
use App\GraphQL\Objects\MessageInput;
use App\Models\Document;
use Illuminate\Support\Arr;

class RequestContractChange {
    public function __construct(
        protected Create $mutation,
    ) {
        // empty
    }

    /**
     *  @param array{input: array<string, mixed>} $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $contract = Document::query()->whereKey($args['input']['contract_id'])->firstOrFail();
        $input    = Arr::except($args['input'], ['from', 'contract_id']);
        $message  = new MessageInput($input);
        $request  = $this->mutation->createRequest($contract, $message);

        return [
            'created' => $request,
        ];
    }
}
