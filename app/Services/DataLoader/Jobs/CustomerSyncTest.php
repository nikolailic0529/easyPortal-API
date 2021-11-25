<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateCustomer;
use App\Utils\Console\CommandFailed;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomerSync
 */
class CustomerSyncTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
        ?bool $warrantyCheck,
        ?bool $withAssets,
        ?bool $withAssetsDocuments,
    ): void {
        $this->override(Kernel::class, static function (MockInterface $mock) use ($expected): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateCustomer::class, $expected)
                ->once()
                ->andReturn(Command::SUCCESS);
        });

        $this->app->make(CustomerSync::class)
            ->init(
                id             : $customerId,
                warrantyCheck  : $warrantyCheck,
                assets         : $withAssets,
                assetsDocuments: $withAssetsDocuments,
            )
            ->run();
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeFailed(): void {
        $this->expectException(CommandFailed::class);

        $this->override(Kernel::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('call')
                ->once()
                ->andReturn(Command::FAILURE);
        });

        $this->app->make(CustomerSync::class)
            ->init(
                id: $this->faker->uuid,
            )
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
                null,
            ],
            'customer with assets and documents'         => [
                [
                    'id'                 => 'd43cb8ab-fae5-4d04-8407-15d979145deb',
                    '--assets'           => true,
                    '--assets-documents' => true,
                ],
                'd43cb8ab-fae5-4d04-8407-15d979145deb',
                null,
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
                null,
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
                null,
                true,
                false,
            ],
            'customer with warranty check'               => [
                [
                    'id'               => 'af894f73-a0c5-488c-9221-31b1705351bb',
                    '--warranty-check' => true,
                ],
                'af894f73-a0c5-488c-9221-31b1705351bb',
                true,
                null,
                null,
            ],
            'customer without warranty check'            => [
                [
                    'id'                  => 'af894f73-a0c5-488c-9221-31b1705351bb',
                    '--no-warranty-check' => true,
                ],
                'af894f73-a0c5-488c-9221-31b1705351bb',
                false,
                null,
                null,
            ],
        ];
    }
    // </editor-fold>
}
