<?php declare(strict_types = 1);

namespace App\Services\Settings\Validation;

use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Setting;
use App\Services\Settings\Types\Type;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Rule;
use ReflectionClassConstant;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Validation\SettingValue
 */
class SettingValueTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, string $class, string $name, string $value): void {
        $validator = $this->app->make(Factory::class);
        $setting   = new Setting(new ReflectionClassConstant($class, $name));
        $rule      = new SettingValue($validator, $setting);

        self::assertEquals($expected, $rule->passes('test', $value));
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.setting' => 'The :setting is invalid: :messages.',
                ],
            ];
        });

        $validator = $this->app->make(Factory::class);
        $setting   = new Setting(new ReflectionClassConstant(SettingValueTest_Constants::class, 'VALUE'));
        $rule      = new SettingValue($validator, $setting);

        self::assertFalse($rule->passes('test', 'invalid'));
        self::assertEquals('The VALUE is invalid: The selected value is invalid.', $rule->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'valid'                    => [true, SettingValueTest_Constants::class, 'VALUE', 'valid'],
            'invalid'                  => [false, SettingValueTest_Constants::class, 'VALUE', 'invalid value'],
            'valid array'              => [true, SettingValueTest_Constants::class, 'ARRAY', 'valid,valid'],
            'valid array (whitespace)' => [true, SettingValueTest_Constants::class, 'ARRAY', 'valid, valid'],
            'invalid array'            => [false, SettingValueTest_Constants::class, 'ARRAY', 'valid,invalid value'],
        ];
    }
    // </editor-fold>
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SettingValueTest_Constants {
    #[SettingAttribute('config.path')]
    #[TypeAttribute(SettingValueTest_Type::class)]
    public const VALUE = 123.4;

    #[SettingAttribute('config.path')]
    #[TypeAttribute(SettingValueTest_Type::class)]
    public const ARRAY = ['test'];
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class SettingValueTest_Type extends Type {
    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        return [Rule::in('valid')];
    }
}
