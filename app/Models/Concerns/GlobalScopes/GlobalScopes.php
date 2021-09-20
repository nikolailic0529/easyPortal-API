<?php declare(strict_types = 1);

namespace App\Models\Concerns\GlobalScopes;

use Closure;

class GlobalScopes {
    /**
     * @template T
     *
     * @param class-string<\App\Models\Concerns\GlobalScopes\DisableableScope> $scope
     * @param \Closure():T $closure
     *
     * @return T
     */
    public static function callWithoutGlobalScope(string $scope, Closure $closure): mixed {
        return State::callWithout([$scope], $closure);
    }

    /**
     * @template T
     *
     * @param array<class-string<\App\Models\Concerns\GlobalScopes\DisableableScope>> $scopes
     * @param \Closure():T $closure
     *
     * @return T
     */
    public static function callWithoutGlobalScopes(array $scopes, Closure $closure): mixed {
        return State::callWithout($scopes, $closure);
    }

    /**
     * @param class-string<\App\Models\Concerns\GlobalScopes\DisableableScope> $scope
     */
    public static function setGlobalScopeDisabled(string $scope, bool $disabled): bool {
        return State::setDisabled($scope, $disabled);
    }
}
