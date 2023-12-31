<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Mutation\Rules;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\InvokableRule as InvokableRuleContract;
use Illuminate\Contracts\Validation\Rule as RuleContract;

abstract class CustomRule extends Rule {
    public function __construct(
        protected Container $container,
    ) {
        parent::__construct();
    }

    public function getRule(): RuleContract|InvokableRuleContract {
        return $this->container->make($this->getRuleClass(), $this->getRuleArguments());
    }

    /**
     * @return class-string<RuleContract|InvokableRuleContract>
     */
    abstract protected function getRuleClass(): string;

    /**
     * @return array<string, mixed>
     */
    protected function getRuleArguments(): array {
        if (!isset($this->directiveArgs)) {
            $this->loadArgValues();
        }

        return $this->directiveArgs;
    }
}
