<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Audits\Audit;
use App\Utils\Eloquent\Model;
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

        if (isset($context['properties']) && !$this->gate->check('administer') && $audit->object_type) {
            $model                 = Relation::getMorphedModel($audit->object_type) ?? $audit->object_type;
            $model                 = new $model();
            $context['properties'] = (new class() extends Model {
                /**
                 * @param array<mixed> $values
                 *
                 * @return array<mixed>
                 */
                public function getModelArrayableItems(Model $model, array $values): array {
                    return $model->getArrayableItems($values);
                }
            })->getModelArrayableItems($model, $context['properties']);
        }

        return json_encode($context);
    }
}
