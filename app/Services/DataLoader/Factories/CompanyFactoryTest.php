<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Model;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\StatusResolver;
use App\Services\DataLoader\Schema\Company as CompanyObject;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Testing\Helper;
use Closure;
use Exception;
use Tests\TestCase;

use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\CompanyFactory
 */
class CompanyFactoryTest extends TestCase {
    use Helper;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::companyStatuses
     */
    public function testCompanyStatuses(): void {
        // Prepare
        $owner   = new class() extends Model {
            public function getMorphClass(): string {
                return $this::class;
            }
        };
        $factory = new class(
            $this->app->make(Normalizer::class),
            $this->app->make(StatusResolver::class),
        ) extends CompanyFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected StatusResolver $statuses,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            /**
             * @inheritDoc
             */
            public function companyStatuses(Model $owner, CompanyObject $company): array {
                return parent::companyStatuses($owner, $company);
            }
        };

        // Null
        $this->assertEmpty($factory->companyStatuses($owner, new CompanyObject(['status' => null])));

        // Empty
        $this->assertEmpty($factory->companyStatuses($owner, new CompanyObject(['status' => ['', null]])));

        // Not empty
        $company  = new CompanyObject([
            'status' => ['a', 'A', 'b'],
        ]);
        $statuses = $factory->companyStatuses($owner, $company);
        $expected = [
            'a' => [
                'key'  => 'a',
                'name' => 'a',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'b',
            ],
        ];

        $this->assertCount(2, $statuses);
        $this->assertEquals($expected, $this->getStatuses($statuses));
    }

    /**
     * @covers ::companyType
     *
     * @dataProvider dataProviderCompanyType
     */
    public function testCompanyType(string|Exception $expected, Closure $typesFactory): void {
        // Prepare
        $factory = new class() extends CompanyFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function type(Model $model, string $type): TypeModel {
                return TypeModel::factory()->make([
                    'object_type' => $model,
                    'key'         => $type,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function companyType(Model $owner, array $types): TypeModel {
                return parent::companyType($owner, $types);
            }
        };

        // Test
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $owner = new class() extends Model {
            // empty
        };

        $types  = $typesFactory($this);
        $actual = $factory->companyType($owner, $types);

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual->key);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCompanyType(): array {
        return [
            'one value'                => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value';
                        }),
                    ];
                },
            ],
            'several values, but same' => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value';
                        }),
                    ];
                },
            ],
            'several values'           => [
                new DataLoaderException('Multiple type.'),
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value a';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->type = 'value b';
                        }),
                    ];
                },
            ],
            'empty'                    => [
                new DataLoaderException('Type is missing.'),
                static function (): array {
                    return [];
                },
            ],
        ];
    }
    // </editor-fold>
}
