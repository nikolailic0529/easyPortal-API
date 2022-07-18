<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Mutations\Message\Create;
use App\GraphQL\Objects\MessageInput;
use App\Models\Customer;
use Illuminate\Support\Arr;

class RequestCustomerChange {
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
        $customer = Customer::query()->whereKey($args['input']['customer_id'])->firstOrFail();
        $input    = Arr::except($args['input'], ['from', 'customer_id']);
        $message  = new MessageInput($input);
        $request  = $this->mutation->createRequest($customer, $message);

        return [
            'created' => $request,
        ];
    }
}
