<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\Organization;
use App\Services\Organization\OrganizationProvider;
use Exception;
use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Service
 */
class ServiceTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::getKeyPart
     *
     * @dataProvider dataProviderGetKey
     */
    public function testGetKeyPart(Exception|string $expected, object|string $value): void {
        $service = new class() extends Service {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty;
            }

            public function getKeyPart(object|string $value): string {
                return parent::getKeyPart($value);
            }
        };

        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $this->assertEquals($expected, $service->getKeyPart($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{\Exception|string,object|string}>
     */
    public function dataProviderGetKey(): array {
        return [
            'directive'         => [
                '@directive',
                new ServiceTest_Directive(),
            ],
            'organization'      => [
                'db5b78c0-65a3-4cba-b045-a985f79b3abb',
                new ServiceTest_Organization('db5b78c0-65a3-4cba-b045-a985f79b3abb', false),
            ],
            'root organization' => [
                '00000000-0000-0000-0000-000000000000',
                new ServiceTest_Organization('91f3204d-427b-40ad-ae19-187ce41d987c', true),
            ],
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
class ServiceTest_Directive extends BaseDirective {
    public static function definition(): string {
        return '';
    }

    public function name(): string {
        return 'directive';
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class ServiceTest_Organization extends OrganizationProvider {
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(
        protected string $key,
        protected bool $root,
    ) {
        // empty
    }

    public function getKey(): string {
        return $this->key;
    }

    public function isRoot(): bool {
        return $this->root;
    }

    protected function getCurrent(): ?Organization {
        return null;
    }
}
