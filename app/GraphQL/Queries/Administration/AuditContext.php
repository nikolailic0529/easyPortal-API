<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Audits\Audit;
use App\Services\Audit\Contracts\Auditable;
use App\Services\Audit\Enums\Action;
use App\Services\Audit\Listeners\AuditableListener;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

use function array_diff;
use function array_fill_keys;
use function array_keys;
use function array_merge;
use function array_values;
use function is_a;
use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

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
        // Default
        $secret  = '********';
        $context = $audit->context;

        // Hide hidden/internal model properties
        if ($this->isModelEvent($audit) && $audit->object_type && $context) {
            $model  = Relation::getMorphedModel($audit->object_type) ?? $audit->object_type;
            $model  = is_a($model, Model::class, true) ? new $model() : null;
            $hidden = array_merge(
                // Hidden properties should not be visible, because it can be insecure
                // (https://laravel.com/docs/9.x/eloquent-serialization)
                $this->getModelHiddenProperties($model, $context),
                // Internal attributes
                $this->getModelInternalProperties($model, $context),
            );

            foreach ($hidden as $property) {
                // Properties
                $propertyValue = $context[AuditableListener::PROPERTIES][$property] ?? null;

                if (is_array($propertyValue)) {
                    $context[AuditableListener::PROPERTIES][$property] = [
                        'value'    => ($propertyValue['value'] ?? null) !== null
                            ? $secret
                            : null,
                        'previous' => ($propertyValue['previous'] ?? null) !== null
                            ? $secret
                            : null,
                    ];
                }

                // Relations
                $isRelation = isset($context[AuditableListener::RELATIONS])
                    && is_array($context[AuditableListener::RELATIONS])
                    && isset($context[AuditableListener::RELATIONS][$property]);

                if ($isRelation) {
                    $context[AuditableListener::RELATIONS][$property] = [
                        'type'    => $secret,
                        'added'   => [$secret],
                        'deleted' => [$secret],
                    ];
                }
            }
        }

        // Return
        return json_encode($context, JSON_THROW_ON_ERROR);
    }

    protected function isModelEvent(Audit $audit): bool {
        return $audit->action === Action::modelCreated()
            || $audit->action === Action::modelUpdated()
            || $audit->action === Action::modelDeleted()
            || $audit->action === Action::modelRestored();
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string>
     */
    protected function getModelHiddenProperties(?Model $model, array $context): array {
        $properties = $this->getModelProperties($context);
        $hidden     = $properties;

        if ($model) {
            $visible = (new class() extends Model {
                /**
                 * @param array<string, mixed> $values
                 *
                 * @return array<string, mixed>
                 */
                public function getModelArrayableItems(Model $model, array $values): array {
                    return $model->getArrayableItems($values);
                }
            })->getModelArrayableItems($model, array_fill_keys($properties, null));
            $hidden  = array_values(array_diff($properties, array_keys($visible)));
        }

        return $hidden;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string>
     */
    protected function getModelInternalProperties(?Model $model, array $context): array {
        // Administer?
        if ($this->gate->check('administer')) {
            return [];
        }

        // Auditable?
        $internal = $model instanceof Auditable
            ? $model->getInternalAttributes()
            : $this->getModelProperties($context);

        return $internal;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string>
     */
    private function getModelProperties(array $context): array {
        $properties = array_merge(
            (array) ($context[AuditableListener::PROPERTIES] ?? null),
            (array) ($context[AuditableListener::RELATIONS] ?? null),
        );
        $properties = array_keys($properties);

        return $properties;
    }
}
