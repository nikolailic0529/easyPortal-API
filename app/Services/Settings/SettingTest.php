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
use Illuminate\Config\Repository;
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

        $this->expectExceptionObject(new InvalidArgumentException(sprintf(
            'The `$constant` must have one of the following attributes `%s`.',
            implode('`, `', [
                ServiceAttribute::class,
                JobAttribute::class,
                SettingAttribute::class,
            ]),
        )));

        new Setting(new Repository(), $const);
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
        $setting = new Setting(new Repository(), $const);

        $this->assertEquals('config.path', $setting->getPath());
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
        $setting = new Setting(new Repository(), $const);

        $this->assertNull($setting->getPath());
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
        $setting = new Setting(new Repository(), $const);

        $this->assertEquals('TEST', $setting->getName());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'TEST'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'SECRET'));

        $this->assertFalse($a->isSecret());
        $this->assertTrue($b->isSecret());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'TEST'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'ARRAY'));

        $this->assertFalse($a->isArray());
        $this->assertTrue($b->isArray());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'TEST'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'INTERNAL'));

        $this->assertFalse($a->isInternal());
        $this->assertTrue($b->isInternal());
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
        $string  = new Setting(new Repository(), new ReflectionClassConstant($class, 'STRING'));
        $integer = new Setting(new Repository(), new ReflectionClassConstant($class, 'INTEGER'));
        $boolean = new Setting(new Repository(), new ReflectionClassConstant($class, 'BOOLEAN'));
        $float   = new Setting(new Repository(), new ReflectionClassConstant($class, 'FLOAT'));
        $url     = new Setting(new Repository(), new ReflectionClassConstant($class, 'URL'));
        $array   = new Setting(new Repository(), new ReflectionClassConstant($class, 'ARRAY'));

        $this->assertInstanceOf(StringType::class, $string->getType());
        $this->assertInstanceOf(IntType::class, $integer->getType());
        $this->assertInstanceOf(BooleanType::class, $boolean->getType());
        $this->assertInstanceOf(FloatType::class, $float->getType());
        $this->assertInstanceOf(Url::class, $url->getType());
        $this->assertInstanceOf(StringType::class, $array->getType());
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
        $string  = new Setting(new Repository(), new ReflectionClassConstant($class, 'STRING'));
        $integer = new Setting(new Repository(), new ReflectionClassConstant($class, 'INTEGER'));
        $boolean = new Setting(new Repository(), new ReflectionClassConstant($class, 'BOOLEAN'));
        $float   = new Setting(new Repository(), new ReflectionClassConstant($class, 'FLOAT'));
        $url     = new Setting(new Repository(), new ReflectionClassConstant($class, 'URL'));
        $array   = new Setting(new Repository(), new ReflectionClassConstant($class, 'ARRAY'));

        $this->assertEquals('String', $string->getTypeName());
        $this->assertEquals('Int', $integer->getTypeName());
        $this->assertEquals('Boolean', $boolean->getTypeName());
        $this->assertEquals('Float', $float->getTypeName());
        $this->assertEquals('Url', $url->getTypeName());
        $this->assertEquals('String', $array->getTypeName());
    }

    /**
     * @covers ::getValue
     */
    public function testGetValue(): void {
        $config = new Repository(['a' => 'aaaaa', 'c' => 'secret', 'f' => [1, 2]]);
        $class  = new class() {
            #[SettingAttribute('a')]
            public const A = 'test';

            #[SettingAttribute('b')]
            public const B = 'test';

            #[SettingAttribute('c')]
            #[SecretAttribute]
            public const C = 'test';

            #[SettingAttribute('d')]
            #[SecretAttribute]
            public const D = 'test';

            #[SettingAttribute('d')]
            #[TypeAttribute(StringType::class)]
            public const E = null;

            #[SettingAttribute('f')]
            #[TypeAttribute(IntType::class)]
            #[SecretAttribute]
            public const F = [1, 2, 3];

            #[SettingAttribute()]
            public const G = 'test';
        };
        $a      = new Setting($config, new ReflectionClassConstant($class, 'A'));
        $b      = new Setting($config, new ReflectionClassConstant($class, 'B'));
        $c      = new Setting($config, new ReflectionClassConstant($class, 'C'));
        $d      = new Setting($config, new ReflectionClassConstant($class, 'D'));
        $e      = new Setting($config, new ReflectionClassConstant($class, 'E'));
        $f      = new Setting($config, new ReflectionClassConstant($class, 'F'));
        $g      = new Setting($config, new ReflectionClassConstant($class, 'G'));

        $this->assertEquals('aaaaa', $a->getValue());
        $this->assertNull($b->getValue());
        $this->assertEquals('secret', $c->getValue());
        $this->assertNull($d->getValue());
        $this->assertNull($e->getValue());
        $this->assertEquals([1, 2], $f->getValue());
        $this->assertEquals('test', $g->getValue());
    }

    /**
     * @covers ::getDefaultValue
     */
    public function testGetDefaultValue(): void {
        $config = new Repository(['a' => 'aaaaa', 'c' => 'secret']);
        $class  = new class() {
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
        $a      = new Setting($config, new ReflectionClassConstant($class, 'A'));
        $b      = new Setting($config, new ReflectionClassConstant($class, 'B'));
        $c      = new Setting($config, new ReflectionClassConstant($class, 'C'));
        $d      = new Setting($config, new ReflectionClassConstant($class, 'D'));
        $e      = new Setting($config, new ReflectionClassConstant($class, 'E'));

        $this->assertEquals('test', $a->getDefaultValue());
        $this->assertEquals('test', $b->getDefaultValue());
        $this->assertEquals('test', $c->getDefaultValue());
        $this->assertNull($d->getDefaultValue());
        $this->assertEquals([1, 2, 3], $e->getDefaultValue());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));
        $c     = new Setting(new Repository(), new ReflectionClassConstant($class, 'C'));

        $this->setTranslations(static function (TestCase $test, string $locale): array {
            return [
                $locale => [
                    'settings.descriptions.B' => 'translated',
                ],
            ];
        });

        $this->assertEquals(
            <<<'DESC'
            Summary summary summary summary summary summary summary.

            Description description description description description
            description description description description description.

            Description description description description description
            description description description description description.
            DESC,
            $a->getDescription(),
        );
        $this->assertEquals('translated', $b->getDescription());
        $this->assertNull($c->getDescription());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));
        $c     = new Setting(new Repository(), new ReflectionClassConstant($class, 'C'));

        $this->setTranslations(static function (TestCase $test, string $locale, string $fallback): array {
            return [
                $locale => [
                    'settings.groups.test' => 'translated',
                ],
            ];
        });

        $this->assertEquals('translated', $a->getGroup());
        $this->assertEquals('untranslated', $b->getGroup());
        $this->assertNull($c->getGroup());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));

        $this->assertFalse($a->isService());
        $this->assertTrue($b->isService());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));

        $this->assertNull($a->getService());
        $this->assertEquals(stdClass::class, $b->getService());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));

        $this->assertFalse($a->isJob());
        $this->assertTrue($b->isJob());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));

        $this->assertNull($a->getJob());
        $this->assertEquals(stdClass::class, $b->getJob());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));

        $this->assertFalse($a->isPublic());
        $this->assertTrue($b->isPublic());
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
        $a     = new Setting(new Repository(), new ReflectionClassConstant($class, 'A'));
        $b     = new Setting(new Repository(), new ReflectionClassConstant($class, 'B'));

        $this->assertNull($a->getPublicName());
        $this->assertEquals('publicNameB', $b->getPublicName());
    }
}
