<?php declare(strict_types = 1);

namespace App\GraphQL\Providers;

use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QuerySecurityRule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Nuwave\Lighthouse\Execution\ValidationRulesProvider as LighthouseValidationRulesProvider;

use function array_merge;

class ValidationRulesProvider extends LighthouseValidationRulesProvider {
    public function __construct(
        Repository $config,
        protected Gate $gate,
    ) {
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function validationRules(): ?array {
        $rules   = parent::validationRules();
        $default = $this->isRuleDefaultEnabled($rules[DisableIntrospection::class] ?? null);
        $enabled = $this->isRuleEnabled($default)
            ? DisableIntrospection::ENABLED
            : DisableIntrospection::DISABLED;

        return array_merge((array) $rules, [
            DisableIntrospection::class => new DisableIntrospection($enabled),
        ]);
    }

    protected function isRuleEnabled(bool $default): bool {
        return $default || $this->gate->denies('graphql-introspection');
    }

    protected function isRuleDefaultEnabled(mixed $rule): bool {
        return $rule instanceof QuerySecurityRule
            && (new class($rule) extends QuerySecurityRule {
                public function __construct(
                    protected QuerySecurityRule $rule,
                ) {
                    // empty
                }

                public function isEnabled(): bool {
                    return $this->rule->isEnabled();
                }
            })->isEnabled();
    }
}
