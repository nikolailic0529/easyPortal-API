<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Concerns\GlobalScopes\GlobalScopes;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Testing\Data\DataGenerator;
use App\Services\DataLoader\Testing\FakeClient;
use App\Services\Organization\Eloquent\OwnedByOrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Tests\Data\DataLoader\AssetsImporterData;
use Tests\Helpers\SequenceUuidFactory;
use Tests\TestCase;

use function array_map;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    use WithQueryLog;
    use GlobalScopes;

    /**
     * @covers ::import
     */
    public function testImport(): void {
        // Generate
        $context = $this->app->make(DataGenerator::class)->generate(AssetsImporterData::class);

        // Setup
        Date::setTestNow('2021-08-30T00:00:00.000+00:00');
        Str::createUuidsUsing(new SequenceUuidFactory());

        $this->override(Client::class, function (): Client {
            return $this->app->make(FakeClient::class)->setData(AssetsImporterData::class);
        });

        // Prepare
        if ($context[AssetsImporterData::CONTEXT_OEMS]) {
            $this
                ->artisan('ep:data-loader-import-oems', [
                    'file' => $this
                        ->getTestData(AssetsImporterData::class)
                        ->path($context[AssetsImporterData::CONTEXT_OEMS]),
                ])
                ->assertExitCode(Command::SUCCESS);
        }

        if ($context[AssetsImporterData::CONTEXT_DISTRIBUTORS]) {
            $this
                ->artisan('ep:data-loader-update-distributor', [
                    'id'       => $context[AssetsImporterData::CONTEXT_DISTRIBUTORS],
                    '--create' => true,
                ])
                ->assertExitCode(Command::SUCCESS);
        }

        if ($context[AssetsImporterData::CONTEXT_RESELLERS]) {
            $this
                ->artisan('ep:data-loader-update-reseller', [
                    'id'       => $context[AssetsImporterData::CONTEXT_RESELLERS],
                    '--create' => true,
                ])
                ->assertExitCode(Command::SUCCESS);
        }

        if ($context[AssetsImporterData::CONTEXT_CUSTOMERS]) {
            $this
                ->artisan('ep:data-loader-update-customer', [
                    'id'       => $context[AssetsImporterData::CONTEXT_CUSTOMERS],
                    '--create' => true,
                ])
                ->assertExitCode(Command::SUCCESS);
        }

        // Test
        $queries  = $this->getQueryLog();
        $importer = $this->app->make(AssetsImporter::class);

        $importer->import(true, chunk: AssetsImporterData::CHUNK, limit: AssetsImporterData::LIMIT);

        $actual = $queries->get();
        $actual = array_map(function (array $query): array {
            return $this->cleanup($query);
        }, $actual);

        $this->assertCount(1496, $actual);
        $this->assertEquals($this->getTestData()->json(), $actual);

        // Objects
        $actual   = [];
        $expected = [
            Distributor::class   => 1,
            Reseller::class      => 40,
            Customer::class      => 51,
            Asset::class         => AssetsImporterData::LIMIT,
            AssetWarranty::class => 120,
            Document::class      => 62,
            DocumentEntry::class => 123,
        ];

        foreach ($expected as $model => $count) {
            $actual[$model] = $this->callWithoutGlobalScope(
                OwnedByOrganizationScope::class,
                static function () use ($model): int {
                    return $model::query()->count();
                },
            );
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param array<array{query: string, bindings: array<mixed>, time: float|null}> $query
     *
     * @return array<array{query: string, bindings: array<mixed>}>
     */
    protected function cleanup(array $query): array {
        unset($query['time']);

        return $query;
    }
}
