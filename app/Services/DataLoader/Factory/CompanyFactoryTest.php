<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory;

use App\Services\DataLoader\Resolver\Resolvers\StatusResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\Company as CompanyObject;
use App\Services\DataLoader\Testing\Helper;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\CompanyFactory
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
            $this->app->make(StatusResolver::class),
        ) extends CompanyFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected StatusResolver $statusResolver,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type): ?Model {
                return null;
            }

            public function companyStatuses(Model $owner, CompanyObject $company): Collection {
                return parent::companyStatuses($owner, $company);
            }
        };

        // Null
        self::assertEmpty($factory->companyStatuses($owner, new CompanyObject(['status' => null])));

        // Empty
        self::assertEmpty($factory->companyStatuses($owner, new CompanyObject(['status' => ['', null]])));

        // Not empty
        $company  = new CompanyObject([
            'status' => ['a', 'A', 'b'],
        ]);
        $statuses = $factory->companyStatuses($owner, $company);
        $expected = [
            'a' => [
                'key'  => 'a',
                'name' => 'A',
            ],
            'b' => [
                'key'  => 'b',
                'name' => 'B',
            ],
        ];

        self::assertCount(2, $statuses);
        self::assertEquals($expected, $this->statuses($statuses));
    }
    // </editor-fold>
}
