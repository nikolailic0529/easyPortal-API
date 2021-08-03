<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Search {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }

    /**
     * @param array{search: string} $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection {
        return Asset::search($args['search'])->take(10)->get();
    }
}
