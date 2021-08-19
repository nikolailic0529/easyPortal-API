<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Audits\Audit;
use App\Models\Model;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_key_exists;
use function array_keys;
use function array_map;
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
            $model         = Relation::getMorphedModel($audit->object_type) ?? $audit->object_type;
            $allProperties = array_map(static function ($property) {
                return $property['value'];
            }, $context['properties']); // Needed to fetch correct getArrayableItems
            $properties    = (new class(new $model()) extends Model {
                public function __construct(
                    protected Model $model,
                ) {
                    // empty
                }
                /**
                 * @param array<string, mixed> $values
                 *
                 * @return array<string, mixed>
                 */
                public function getArrayableItems(array $values): array {
                    // getArrayableItems intersect values with visible leading to empty response on empty values
                    // getAttributes will return empty as Model is empty
                    return $this->model->getArrayableItems($values);
                }
            })->getArrayableItems($allProperties);
            $properties    = array_keys($properties);
            foreach ($context['properties'] as $field => $value) {
                if (!in_array($field, $properties, true)) {
                    unset($context['properties'][$field]);
                }
            }
        }
        return json_encode($context);
    }
}
