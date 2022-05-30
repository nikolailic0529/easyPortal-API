<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\GlobalScopes;

use Closure;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class GlobalScopes {
    /**
     * @template T
     *
     * @param class-string<DisableableScope<TModel>> $scope
     * @param Closure():T                            $closure
     *
     * @return T
     */
    public static function callWithoutGlobalScope(string $scope, Closure $closure): mixed {
        return State::callWithout([$scope], $closure);
    }

    /**
     * @template T
     *
     * @param array<class-string<DisableableScope<TModel>>> $scopes
     * @param Closure():T                                   $closure
     *
     * @return T
     */
    public static function callWithoutGlobalScopes(array $scopes, Closure $closure): mixed {
        return State::callWithout($scopes, $closure);
    }

    /**
     * @param class-string<DisableableScope<TModel>> $scope
     */
    public static function setGlobalScopeDisabled(string $scope, bool $disabled): bool {
        return State::setDisabled($scope, $disabled);
    }
}
