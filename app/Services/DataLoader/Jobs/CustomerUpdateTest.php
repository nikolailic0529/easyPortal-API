<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateCustomer;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomerUpdate
 */
class CustomerUpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::uniqueId
     */
    public function testUniqueId(): void {
        $expected = $this->faker->uuid;
        $actual   = $this->app->make(CustomerUpdate::class)->init($expected)->uniqueId();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $expected
     */
    public function testInvoke(
        array $expected,
        string $customerId,
        ?bool $withAssets,
        ?bool $withAssetsDocuments,
    ): void {
        $this->override(Kernel::class, static function (MockInterface $mock) use ($expected): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateCustomer::class, $expected)
                ->once();
        });

        $this->app->make(CustomerUpdate::class)
            ->init($customerId, $withAssets, $withAssetsDocuments)
            ->run();
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string,?bool,?bool}>
     */
    public function dataProviderInvoke(): array {
        return [
            'customer only'                              => [
                [
                    'id' => '48c485a3-f0ed-44e5-9bc4-7ea28fba98ae',
                ],
                '48c485a3-f0ed-44e5-9bc4-7ea28fba98ae',
                null,
                null,
            ],
            'customer with assets and documents'         => [
                [
                    'id'                 => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                    '--assets'           => true,
                    '--assets-documents' => true,
                ],
                'd43cb8ab-fae5-4d04-8407-15d979145deb',
                true,
                true,
            ],
            'customer without assets and documents'      => [
                [
                    'id'                    => 'a2ff9b08-0404-4bde-a400-288d6ce4a1c8',
                    '--no-assets'           => true,
                    '--no-assets-documents' => true,
                ],
                'a2ff9b08-0404-4bde-a400-288d6ce4a1c8',
                false,
                false,
            ],
            'customer with assets and without documents' => [
                [
                    'id'                    => '347e5072-9cd8-42a7-a1be-47f329a9e3eb',
                    '--assets'              => true,
                    '--no-assets-documents' => true,
                ],
                '347e5072-9cd8-42a7-a1be-47f329a9e3eb',
                true,
                false,
            ],
        ];
    }
    // </editor-fold>
}
