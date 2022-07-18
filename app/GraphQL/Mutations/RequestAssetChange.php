<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Mutations\Message\Create;
use App\GraphQL\Objects\MessageInput;
use App\Models\Asset;
use Illuminate\Support\Arr;

/**
 * @deprecated {@see \App\GraphQL\Mutations\Asset\ChangeRequest\Create}
 */
class RequestAssetChange {
    public function __construct(
        protected Create $mutation,
    ) {
        // empty
    }

    /**
     * @param array{input: array<string, mixed>} $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke(mixed $root, array $args): array {
        $asset   = Asset::query()->whereKey($args['input']['asset_id'])->firstOrFail();
        $input   = Arr::except($args['input'], ['from', 'asset_id']);
        $message = new MessageInput($input);
        $request = $this->mutation->createRequest($asset, $message);

        return [
            'created' => $request,
        ];
    }
}
