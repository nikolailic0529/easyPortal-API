<?php declare(strict_types = 1);

namespace App\GraphQL\Unions;

use App\Models\ChangeRequest;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\QuoteRequest;
use App\Models\Role;
use App\Models\User;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Auditable {
    public function __construct(
        protected TypeRegistry $typeRegistry,
    ) {
        // empty
    }

    public function __invoke(mixed $root, GraphQLContext $context, ResolveInfo $resolveInfo): Type {
        $type = match (true) {
            $root instanceof ChangeRequest => 'ChangeRequest',
            $root instanceof QuoteRequest  => 'QuoteRequest',
            $root instanceof Organization  => 'Organization',
            $root instanceof Invitation    => 'Invitation',
            $root instanceof User          => 'User',
            $root instanceof Role          => 'Role',
            default                        => 'Unknown',
        };
        $type = $this->typeRegistry->get($type);

        return $type;
    }
}
