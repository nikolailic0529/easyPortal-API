<?php declare(strict_types = 1);

namespace App\PhpStan\Extensions\I18n;

use App\Services\I18n\Translation\TranslationLoader;
use Illuminate\Foundation\Application;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

use function array_key_exists;

/**
 * @internal
 */
final class Extension implements DynamicFunctionReturnTypeExtension {
    use Concerns\HasContainer;

    /**
     * @var array<string, string>|null
     */
    private ?array $translations = null;

    public function isFunctionSupported(FunctionReflection $functionReflection): bool {
        return $functionReflection->getName() === 'trans'
            || $functionReflection->getName() === '__';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): ?Type {
        $type = null;
        $key  = $functionCall->args[0] instanceof Arg
            ? $functionCall->args[0]->value
            : null;

        if ($key instanceof String_) {
            $translations = $this->getTranslations();

            if (array_key_exists($key->value, $translations)) {
                $type = $scope->getTypeFromValue($translations[$key->value]);
            }
        }

        return $type;
    }

    /**
     * @return array<string, string>
     */
    protected function getTranslations(): array {
        // Loaded?
        if (isset($this->translations)) {
            return $this->translations;
        }

        // Load
        $loader             = $this->resolve(TranslationLoader::class);
        $container          = $this->getContainer();
        $this->translations = [];

        if ($container instanceof Application && $loader instanceof TranslationLoader) {
            $this->translations = $loader->getTranslations($container->getFallbackLocale());
        }

        return $this->translations;
    }
}
