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
     * @param array<class-string<DisableableScope<TModel>>>|class-string<DisableableScope<TModel>> $scope
     * @param Closure():T                                                                          $closure
     *
     * @return T
     */
    public static function callWithout(array|string $scope, Closure $closure): mixed {
        return State::callWithout((array) $scope, $closure);
    }

    /**
     * @template T
     *
     * @param Closure():T $closure
     *
     * @return T
     */
    public static function callWithoutAll(Closure $closure): mixed {
        return State::callWithoutAll($closure);
    }

    /**
     * @param class-string<DisableableScope<TModel>> $scope
     */
    public static function setDisabled(string $scope, bool $disabled): bool {
        return State::setDisabled($scope, $disabled);
    }

    public static function setDisabledAll(bool $disabled): bool {
        return State::setDisabledAll($disabled);
    }
}
