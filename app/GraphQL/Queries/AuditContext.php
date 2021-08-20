<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Audits\Audit;
use App\Models\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function json_encode;

class AuditContext {
    public function __construct(
        protected Gate $gate,
    ) {
        // empty
    }

    /**
     * @param array<mixed> $args
     */
    public function __invoke(Audit $audit, array $args, GraphQLContext $graphqlContext, ResolveInfo $info): ?string {
        $context = $audit->context;

        if (isset($context['properties']) && !$this->gate->check('administer')) {
            $model                 = Relation::getMorphedModel($audit->object_type) ?? $audit->object_type;
            $context['properties'] = (new class(new $model()) extends Model {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct(
                    protected Model $model,
                ) {
                    // empty
                }

                /**
                 * @inheritDoc
                 */
                public function getArrayableItems(array $values): array {
                    return $this->model->getArrayableItems($values);
                }
            })->getArrayableItems($context['properties']);
        }

        return json_encode($context);
    }
}
