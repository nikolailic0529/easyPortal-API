<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Commands\UpdateDocument;
use App\Utils\Console\CommandFailed;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\DocumentSync
 */
class DocumentSyncsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed> $expected
     */
    public function testInvoke(array $expected, string $documentId): void {
        $this->override(Kernel::class, static function (MockInterface $mock) use ($expected): void {
            $mock
                ->shouldReceive('call')
                ->with(UpdateDocument::class, $expected)
                ->once()
                ->andReturn(Command::SUCCESS);
        });

        $this->app->make(DocumentSync::class)
            ->init($documentId)
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

        $this->app->make(DocumentSync::class)
            ->init($this->faker->uuid)
            ->run();
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array{string,?bool}>
     */
    public function dataProviderInvoke(): array {
        return [
            'document only' => [
                [
                    'id' => 'd3f06a69-43c9-497e-b033-f0928f757126',
                ],
                'd3f06a69-43c9-497e-b033-f0928f757126',
            ],
        ];
    }
    // </editor-fold>
}
