<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Model;
use App\Models\Status;
use App\Models\Type as TypeModel;
use App\Services\DataLoader\Exceptions\DataLoaderException;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Schema\CompanyType;
use App\Services\DataLoader\Schema\Type;
use Closure;
use Exception;
use Tests\TestCase;

use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\Company
 */
class CompanyTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::companyStatus
     *
     * @dataProvider dataProviderCompanyStatus
     */
    public function testCompanyStatus(string|Exception $expected, Closure $statusesFactory): void {
        // Prepare
        $factory = new class() extends ModelFactory {
            use Company {
                companyStatus as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function status(Model $model, string $status): Status {
                return Status::factory()->make([
                    'object_type' => $model,
                    'key'         => $status,
                ]);
            }
        };

        // Test
        if ($expected instanceof Exception) {
            $this->expectExceptionObject($expected);
        }

        $owner = new class() extends Model {
            // empty
        };

        $statuses = $statusesFactory($this);
        $actual   = $factory->companyStatus($owner, $statuses);

        $this->assertNotNull($actual);
        $this->assertEquals($expected, $actual->key);
    }

    /**
     * @covers ::companyType
     *
     * @dataProvider dataProviderCompanyType
     */
    public function testCompanyType(string|Exception $expected, Closure $typesFactory): void {
        // Prepare
        $factory = new class() extends ModelFactory {
            use Company {
                companyType as public;
            }

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
    public function dataProviderCompanyStatus(): array {
        return [
            'one value'                => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value';
                        }),
                    ];
                },
            ],
            'several values, but same' => [
                'value',
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value';
                        }),
                    ];
                },
            ],
            'several values'           => [
                new DataLoaderException('Multiple status.'),
                static function (): array {
                    return [
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value a';
                        }),
                        tap(new CompanyType(), static function (CompanyType $type): void {
                            $type->status = 'value b';
                        }),
                    ];
                },
            ],
            'empty'                    => [
                new DataLoaderException('Status is missing.'),
                static function (): array {
                    return [];
                },
            ],
        ];
    }

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
