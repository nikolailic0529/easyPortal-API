<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Audits\Audit;
use App\Models\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\AuthManager;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_key_exists;
use function in_array;
use function json_encode;

class AuditContext {
    public function __construct(
        protected AuthManager $auth,
    ) {
        // empty
    }
    /**
     * @param array<mixed> $args
     */
    public function __invoke(Audit $audit, array $args, GraphQLContext $graphqlContext, ResolveInfo $info): ?string {
        $context = $audit->context;
        $user    = $this->auth->user();
        if (
            $user &&
            $user->cannot('administer') &&
            array_key_exists('properties', $context)
        ) {
            $model = $audit->model;
            if ($model instanceof Model) {
                $visible = $model->getVisible();
                foreach ($context['properties'] as $field => $value) {
                    if (!in_array($field, $visible, true)) {
                        unset($context['properties'][$field]);
                    }
                }
            }
        }
        return json_encode($context);
    }
}
