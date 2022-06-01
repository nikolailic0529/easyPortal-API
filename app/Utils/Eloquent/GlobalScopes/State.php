<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\GlobalScopes;

use Closure;
use InvalidArgumentException;

use function is_a;
use function sprintf;

// TODO [laravel] Is there is a better a way for this?

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class State {
    /**
     * @var array<class-string<DisableableScope<TModel>>, bool>
     */
    protected static array $disabled    = [];
    protected static bool  $disabledAll = false;

    /**
     * @param class-string<DisableableScope<TModel>> $scope
     */
    public static function isEnabled(string $scope): bool {
        return !self::isDisabled($scope);
    }

    /**
     * @param class-string<DisableableScope<TModel>> $scope
     */
    public static function isDisabled(string $scope): bool {
        return self::$disabledAll || (self::$disabled[$scope] ?? false);
    }

    /**
     * @template T
     *
     * @param array<class-string<DisableableScope<TModel>>> $scopes
     * @param Closure():T                                   $closure
     *
     * @return T
     */
    public static function callWithout(array $scopes, Closure $closure): mixed {
        $previous = [];

        foreach ($scopes as $scope) {
            $previous[$scope] = self::setDisabled($scope, true);
        }

        try {
            return $closure();
        } finally {
            foreach ($previous as $scope => $disabled) {
                self::setDisabled($scope, $disabled);
            }
        }
    }

    /**
     * @template T
     *
     * @param Closure():T $closure
     *
     * @return T
     */
    public static function callWithoutAll(Closure $closure): mixed {
        $previous = self::setDisabledAll(true);

        try {
            return $closure();
        } finally {
            self::setDisabledAll($previous);
        }
    }

    /**
     * @param class-string<DisableableScope<TModel>> $scope
     */
    public static function setDisabled(string $scope, bool $disabled): bool {
        // Can be disabled?
        if (!is_a($scope, DisableableScope::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The `$scope` must be instance of `%s`.',
                DisableableScope::class,
            ));
        }

        // Update
        $previous = self::isDisabled($scope);

        if ($disabled) {
            self::$disabled[$scope] = true;
        } else {
            unset(self::$disabled[$scope]);
        }

        // Return
        return $previous;
    }

    public static function setDisabledAll(bool $disabled): bool {
        $previous          = self::$disabledAll;
        self::$disabledAll = $disabled;

        return $previous;
    }

    public static function reset(): void {
        self::$disabled    = [];
        self::$disabledAll = false;
    }
}
