<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\GlobalScopes;

use Closure;
use InvalidArgumentException;

use function is_a;
use function sprintf;

// TODO [laravel] Is there is a better a way for this?

class State {
    /**
     * @var array<class-string<\App\Utils\Eloquent\GlobalScopes\DisableableScope>, bool>
     */
    protected static array $disabled = [];

    /**
     * @param class-string<\App\Utils\Eloquent\GlobalScopes\DisableableScope> $scope
     */
    public static function isEnabled(string $scope): bool {
        return ! self::isDisabled($scope);
    }

    /**
     * @param class-string<\App\Utils\Eloquent\GlobalScopes\DisableableScope> $scope
     */
    public static function isDisabled(string $scope): bool {
        return self::$disabled[$scope] ?? false;
    }

    /**
     * @template T
     *
     * @param array<class-string<\App\Utils\Eloquent\GlobalScopes\DisableableScope>> $scopes
     * @param \Closure():T $closure
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
     * @param class-string<\App\Utils\Eloquent\GlobalScopes\DisableableScope> $scope
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

    public static function reset(): void {
        self::$disabled = [];
    }
}
