<?php declare(strict_types = 1);

namespace App\PhpStan\Extensions\Settings;

use App\Services\Settings\Setting;
use App\Services\Settings\Settings;
use App\Services\Settings\Types\BooleanType as SettingsBooleanType;
use App\Services\Settings\Types\FloatType as SettingsFloatType;
use App\Services\Settings\Types\IntType as SettingsIntType;
use App\Services\Settings\Types\StringType as SettingsStringType;
use Illuminate\Contracts\Config\Repository;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use Throwable;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;

/**
 * @internal
 */
final class Extension implements DynamicMethodReturnTypeExtension, DynamicFunctionReturnTypeExtension {
    use Concerns\HasContainer;

    /**
     * @var array<string, Type>|null
     */
    private ?array $settings = null;

    // <editor-fold desc="DynamicFunctionReturnTypeExtension">
    // =========================================================================
    public function isFunctionSupported(FunctionReflection $functionReflection): bool {
        return $functionReflection->getName() === 'config';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): ?Type {
        return $this->getType($functionCall);
    }
    // </editor-fold>

    // <editor-fold desc="DynamicMethodReturnTypeExtension">
    // =========================================================================
    public function getClass(): string {
        return Repository::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool {
        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): ?Type {
        return $this->getType($methodCall);
    }
    // </editor-fold>

    // <editor-fold desc="Helpers">
    // =========================================================================
    protected function getType(MethodCall|FuncCall $call): ?Type {
        $type = null;
        $key  = $call->args[0] instanceof Arg
            ? $call->args[0]->value
            : null;

        if ($key instanceof String_) {
            try {
                $base = $this->getSettings()[$key->value] ?? $this->getDynamicType($key->value);

                if ($base) {
                    $type = new UnionType([$base, new NullType()]);
                }
            } catch (Throwable) {
                return new ErrorType();
            }
        }

        return $type;
    }

    protected function getDynamicType(string $setting): ?Type {
        $type   = null;
        $config = $this->resolve(Repository::class);

        if ($config instanceof Repository && $config->has($setting)) {
            $value = $config->get($setting);
            $type  = match (true) {
                is_array($value) => new ArrayType(new MixedType(), new MixedType()),
                is_float($value) => new FloatType(),
                is_int($value)   => new IntegerType(),
                is_bool($value)  => new BooleanType(),
                default          => new StringType(),
            };
        }

        return $type;
    }

    /**
     * @return array<string, Type>
     */
    protected function getSettings(): array {
        // Loaded?
        if (isset($this->settings)) {
            return $this->settings;
        }

        // Load
        $service        = $this->resolve(Settings::class);
        $this->settings = [];

        if ($service instanceof Settings) {
            foreach ($service->getSettings() as $setting) {
                $type = $this->getSettingType($setting);

                if ($type) {
                    $this->settings[$setting->getName()] = $type;
                    $this->settings[$setting->getPath()] = $type;
                }
            }
        }

        return $this->settings;
    }

    protected function getSettingType(Setting $setting): ?Type {
        $valueType   = null;
        $settingType = $setting->getType();

        if ($settingType instanceof SettingsStringType) {
            $valueType = new StringType();
        } elseif ($settingType instanceof SettingsBooleanType) {
            $valueType = new BooleanType();
        } elseif ($settingType instanceof SettingsFloatType) {
            $valueType = new FloatType();
        } elseif ($settingType instanceof SettingsIntType) {
            $valueType = new IntegerType();
        } else {
            // empty
        }

        if ($valueType && $setting->isArray()) {
            $valueType = new ArrayType(new IntegerType(), $valueType);
        }

        return $valueType;
    }
    // </editor-fold>
}
