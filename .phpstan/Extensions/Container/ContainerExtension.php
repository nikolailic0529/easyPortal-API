<?php declare(strict_types = 1);

namespace App\PhpStan\Extensions\Container;

use Illuminate\Contracts\Container\Container;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

use function is_object;

/**
 * @internal
 */
final class ContainerExtension implements DynamicMethodReturnTypeExtension {
    use Concerns\HasContainer;

    public function getClass(): string {
        return Container::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return $methodReflection->getName() === 'make'
            || $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        $type     = null;
        $arg      = $methodCall->args[0] ?? null;
        $expr     = $arg instanceof Arg ? $arg->value : null;
        $exprType = $expr ? $scope->getType($expr) : null;

        if ($expr instanceof ClassConstFetch && $expr->class instanceof FullyQualified) {
            $type = new ObjectType($expr->class->toString());
        } elseif ($exprType instanceof GenericClassStringType) {
            $generic = $exprType->getGenericType();

            if ($generic instanceof ObjectType) {
                $type = $generic;
            }
        } elseif ($expr instanceof String_) {
            $type = $this->getInstanceType($expr->value);
        } else {
            // unknown
        }

        return $type;
    }

    protected function getInstanceType(string $abstract): Type {
        $type = new ErrorType();

        try {
            $resolved = $this->resolve($abstract);
            $type     = is_object($resolved)
                ? new ObjectType($resolved::class)
                : new ErrorType();
        } catch (Throwable) {
            // empty
        }

        return $type;
    }
}
