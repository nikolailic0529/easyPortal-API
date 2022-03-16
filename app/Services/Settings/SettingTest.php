<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Attributes\Group as GroupAttribute;
use App\Services\Settings\Attributes\Internal as InternalAttribute;
use App\Services\Settings\Attributes\Job as JobAttribute;
use App\Services\Settings\Attributes\PublicName as PublicNameAttribute;
use App\Services\Settings\Attributes\Secret as SecretAttribute;
use App\Services\Settings\Attributes\Service as ServiceAttribute;
use App\Services\Settings\Attributes\Setting as SettingAttribute;
use App\Services\Settings\Attributes\Type as TypeAttribute;
use App\Services\Settings\Types\BooleanType;
use App\Services\Settings\Types\FloatType;
use App\Services\Settings\Types\IntType;
use App\Services\Settings\Types\StringType;
use App\Services\Settings\Types\Url;
use InvalidArgumentException;
use ReflectionClassConstant;
use stdClass;
use Tests\TestCase;

use function implode;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\Services\Settings\Setting
 */
class SettingTest extends TestCase {
    /**
     * @covers ::__construct
     */
    public function testConstruct(): void {
        $const = new ReflectionClassConstant(
            new class() {
                public const TEST = 'test';
            },
            'TEST',
        );

        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `$constant` must have one of the following attributes `%s`.',
            implode('`, `', [
                ServiceAttribute::class,
                JobAttribute::class,
                SettingAttribute::class,
            ]),
        )));

        new Setting($const);
    }

    /**
     * @covers ::getPath
     */
    public function testGetPath(): void {
        $const   = new ReflectionClassConstant(
            new class() {
                #[SettingAttribute('config.path')]
                public const TEST = 'test';
            },
            'TEST',
        );
        $setting = new Setting($const);

        self::assertEquals('config.path', $setting->getPath());
    }

    /**
     * @covers ::getPath
     */
    public function testGetPathNull(): void {
        $const   = new ReflectionClassConstant(
            new class() {
                #[SettingAttribute()]
                public const TEST = 'test';
            },
            'TEST',
        );
        $setting = new Setting($const);

        self::assertNull($setting->getPath());
    }

    /**
     * @covers ::getName
     */
    public function testGetName(): void {
        $const   = new ReflectionClassConstant(
            new class() {
                #[SettingAttribute('config.path')]
                public const TEST = 'test';
            },
            'TEST',
        );
        $setting = new Setting($const);

        self::assertEquals('TEST', $setting->getName());
    }

    /**
     * @covers ::isSecret
     */
    public function testIsSecret(): void {
        $class = new class() {
            #[SettingAttribute('config.path')]
            public const TEST = 'test';

            #[SettingAttribute('config.path.secret')]
            #[SecretAttribute]
            public const SECRET = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'TEST'));
        $b     = new Setting(new ReflectionClassConstant($class, 'SECRET'));

        self::assertFalse($a->isSecret());
        self::assertTrue($b->isSecret());
    }

    /**
     * @covers ::isArray
     */
    public function testIsArray(): void {
        $class = new class() {
            #[SettingAttribute('config.path')]
            public const TEST = 'test';

            #[SettingAttribute('config.path.secret')]
            public const ARRAY = ['test'];
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'TEST'));
        $b     = new Setting(new ReflectionClassConstant($class, 'ARRAY'));

        self::assertFalse($a->isArray());
        self::assertTrue($b->isArray());
    }

    /**
     * @covers ::isInternal
     */
    public function testIsInternal(): void {
        $class = new class() {
            #[SettingAttribute('config.path')]
            public const TEST = 'test';

            #[SettingAttribute('config.path.secret')]
            #[InternalAttribute]
            public const INTERNAL = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'TEST'));
        $b     = new Setting(new ReflectionClassConstant($class, 'INTERNAL'));

        self::assertFalse($a->isInternal());
        self::assertTrue($b->isInternal());
    }

    /**
     * @covers ::getType
     */
    public function testGetType(): void {
        $class   = new class() {
            #[SettingAttribute('config.path')]
            public const STRING = 'test';

            #[SettingAttribute('config.path')]
            public const INTEGER = 123;

            #[SettingAttribute('config.path')]
            public const BOOLEAN = true;

            #[SettingAttribute('config.path')]
            public const FLOAT = 123.4;

            #[SettingAttribute('config.path.secret')]
            #[TypeAttribute(Url::class)]
            public const URL = 'test';

            #[SettingAttribute('config.path.secret')]
            #[TypeAttribute(StringType::class)]
            public const ARRAY = ['test'];
        };
        $string  = new Setting(new ReflectionClassConstant($class, 'STRING'));
        $integer = new Setting(new ReflectionClassConstant($class, 'INTEGER'));
        $boolean = new Setting(new ReflectionClassConstant($class, 'BOOLEAN'));
        $float   = new Setting(new ReflectionClassConstant($class, 'FLOAT'));
        $url     = new Setting(new ReflectionClassConstant($class, 'URL'));
        $array   = new Setting(new ReflectionClassConstant($class, 'ARRAY'));

        self::assertInstanceOf(StringType::class, $string->getType());
        self::assertInstanceOf(IntType::class, $integer->getType());
        self::assertInstanceOf(BooleanType::class, $boolean->getType());
        self::assertInstanceOf(FloatType::class, $float->getType());
        self::assertInstanceOf(Url::class, $url->getType());
        self::assertInstanceOf(StringType::class, $array->getType());
    }

    /**
     * @covers ::getTypeName
     */
    public function testGetTypeName(): void {
        $class   = new class() {
            #[SettingAttribute('config.path')]
            public const STRING = 'test';

            #[SettingAttribute('config.path')]
            public const INTEGER = 123;

            #[SettingAttribute('config.path')]
            public const BOOLEAN = true;

            #[SettingAttribute('config.path')]
            public const FLOAT = 123.4;

            #[SettingAttribute('config.path.secret')]
            #[TypeAttribute(Url::class)]
            public const URL = 'test';

            #[SettingAttribute('config.path.secret')]
            #[TypeAttribute(StringType::class)]
            public const ARRAY = ['test'];
        };
        $string  = new Setting(new ReflectionClassConstant($class, 'STRING'));
        $integer = new Setting(new ReflectionClassConstant($class, 'INTEGER'));
        $boolean = new Setting(new ReflectionClassConstant($class, 'BOOLEAN'));
        $float   = new Setting(new ReflectionClassConstant($class, 'FLOAT'));
        $url     = new Setting(new ReflectionClassConstant($class, 'URL'));
        $array   = new Setting(new ReflectionClassConstant($class, 'ARRAY'));

        self::assertEquals('String', $string->getTypeName());
        self::assertEquals('Int', $integer->getTypeName());
        self::assertEquals('Boolean', $boolean->getTypeName());
        self::assertEquals('Float', $float->getTypeName());
        self::assertEquals('Url', $url->getTypeName());
        self::assertEquals('String', $array->getTypeName());
    }

    /**
     * @covers ::getDefaultValue
     */
    public function testGetDefaultValue(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[SettingAttribute('b')]
            public const B = 'test';

            #[SettingAttribute('c')]
            #[SecretAttribute]
            public const C = 'test';

            #[SettingAttribute('d')]
            #[TypeAttribute(IntType::class)]
            public const D = null;

            #[SettingAttribute('e')]
            #[TypeAttribute(IntType::class)]
            #[SecretAttribute]
            public const E = [1, 2, 3];
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));
        $c     = new Setting(new ReflectionClassConstant($class, 'C'));
        $d     = new Setting(new ReflectionClassConstant($class, 'D'));
        $e     = new Setting(new ReflectionClassConstant($class, 'E'));

        self::assertEquals('test', $a->getDefaultValue());
        self::assertEquals('test', $b->getDefaultValue());
        self::assertEquals('test', $c->getDefaultValue());
        self::assertNull($d->getDefaultValue());
        self::assertEquals([1, 2, 3], $e->getDefaultValue());
    }

    /**
     * @covers ::getDescription
     */
    public function testGetDescription(): void {
        $class = new class() {
            /**
             * Summary summary summary summary summary summary summary.
             *
             * Description description description description description
             * description description description description description.
             *
             * Description description description description description
             * description description description description description.
             */
            #[SettingAttribute('a')]
            public const A = 'test';

            #[SettingAttribute('b')]
            public const B = 'test';

            #[SettingAttribute('s')]
            public const C = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));
        $c     = new Setting(new ReflectionClassConstant($class, 'C'));

        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'settings.descriptions.B' => 'translated',
                ],
            ];
        });

        self::assertEquals(
            <<<'DESC'
            Summary summary summary summary summary summary summary.

            Description description description description description
            description description description description description.

            Description description description description description
            description description description description description.
            DESC,
            $a->getDescription(),
        );
        self::assertEquals('translated', $b->getDescription());
        self::assertNull($c->getDescription());
    }

    /**
     * @covers ::getGroup
     */
    public function testGetGroup(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            #[GroupAttribute('test')]
            public const A = 'test';

            #[SettingAttribute('b')]
            #[GroupAttribute('untranslated')]
            public const B = 'test';

            #[SettingAttribute('b')]
            public const C = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));
        $c     = new Setting(new ReflectionClassConstant($class, 'C'));

        $this->setTranslations(static function (TestCase $test, string $locale, string $fallback): array {
            return [
                $locale => [
                    'settings.groups.test' => 'translated',
                ],
            ];
        });

        self::assertEquals('translated', $a->getGroup());
        self::assertEquals('untranslated', $b->getGroup());
        self::assertNull($c->getGroup());
    }

    /**
     * @covers ::isService
     */
    public function testIsService(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[ServiceAttribute(stdClass::class, 'b')]
            public const B = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));

        self::assertFalse($a->isService());
        self::assertTrue($b->isService());
    }

    /**
     * @covers ::getService
     */
    public function testGetService(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[ServiceAttribute(stdClass::class, 'b')]
            public const B = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));

        self::assertNull($a->getService());
        self::assertEquals(stdClass::class, $b->getService());
    }

    /**
     * @covers ::isJob
     */
    public function testIsJob(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[JobAttribute(stdClass::class, 'b')]
            public const B = 'test';

            #[ServiceAttribute(stdClass::class, 'c')]
            public const C = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));

        self::assertFalse($a->isJob());
        self::assertTrue($b->isJob());
    }

    /**
     * @covers ::getJob
     */
    public function testGetJob(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[JobAttribute(stdClass::class, 'b')]
            public const B = 'test';

            #[ServiceAttribute(stdClass::class, 'c')]
            public const C = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));

        self::assertNull($a->getJob());
        self::assertEquals(stdClass::class, $b->getJob());
    }

    /**
     * @covers ::isPublic
     */
    public function testIsPublic(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[SettingAttribute('b')]
            #[PublicNameAttribute('b')]
            public const B = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));

        self::assertFalse($a->isPublic());
        self::assertTrue($b->isPublic());
    }

    /**
     * @covers ::getPublicName
     */
    public function testGetPublicName(): void {
        $class = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[SettingAttribute('b')]
            #[PublicNameAttribute('publicNameB')]
            public const B = 'test';
        };
        $a     = new Setting(new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new ReflectionClassConstant($class, 'B'));

        self::assertNull($a->getPublicName());
        self::assertEquals('publicNameB', $b->getPublicName());
    }
}
