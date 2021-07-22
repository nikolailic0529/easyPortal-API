<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Model;
use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function array_map;
use function array_merge;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Importers\OemsImporter
 */
class OemsImporterTest extends TestCase {
    /**
     * @covers ::import
     * @covers ::onRow
     * @covers ::startRow
     * @covers ::onBeforeSheet
     * @covers ::onAfterSheet
     * @covers ::getCellValue
     * @covers ::parse
     * @covers ::registerEvents
     */
    public function testImport(): void {
        // Helpers
        $toArray = static function (Collection $models): array {
            $convert = static function (Model $model) use (&$convert): array {
                $attributes = Arr::except($model->getAttributes(), [
                    'id',
                    'oem_id',
                    'service_group_id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);
                $relations  = array_map(static function (mixed $value) use ($convert): mixed {
                    return $value instanceof Model ? $convert($value) : $value;
                }, $model->getRelations());
                $properties = array_merge($attributes, $relations);

                return $properties;
            };

            return $models->map($convert)->toArray();
        };

        // Pretest
        $this->assertEquals(0, Oem::query()->count());
        $this->assertEquals(0, ServiceGroup::query()->count());
        $this->assertEquals(0, ServiceLevel::query()->count());

        // Run
        $this->app->make(OemsImporter::class)->import($this->getTestData()->file('.xlsx'));

        // Oems
        $oems = Oem::query()->get();
        $oemA = [
            'key'  => 'ABC',
            'name' => 'ABC',
        ];
        $oemB = [
            'key'  => 'CBA',
            'name' => 'CBA',
        ];

        $this->assertCount(2, $oems);
        $this->assertEqualsCanonicalizing([$oemA, $oemB], $toArray($oems));

        // Service Groups
        $groups  = ServiceGroup::query()
            ->with('oem')
            ->get();
        $groupAA = [
            'sku'  => 'GA',
            'name' => 'Group A',
            'oem'  => $oemA,
        ];
        $groupAB = [
            'sku'  => 'GB',
            'name' => 'Group B',
            'oem'  => $oemA,
        ];
        $groupAC = [
            'sku'  => 'GC',
            'name' => 'Group C',
            'oem'  => $oemA,
        ];
        $groupBA = [
            'sku'  => 'GA',
            'name' => 'Group A',
            'oem'  => $oemB,
        ];

        $this->assertCount(4, $groups);
        $this->assertEqualsCanonicalizing(
            [$groupAA, $groupAB, $groupAC, $groupBA],
            $toArray($groups),
        );

        // Service Levels
        $levels = ServiceLevel::query()
            ->with('oem')
            ->with('serviceGroup')
            ->with('serviceGroup.oem')
            ->get();

        $this->assertCount(5, $levels);
        $this->assertEqualsCanonicalizing(
            [
                [
                    'sku'          => 'LA',
                    'name'         => 'Level LA',
                    'description'  => "Level LA Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAA,
                ],
                [
                    'sku'          => 'LB',
                    'name'         => 'Level LB',
                    'description'  => "Level LB Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAB,
                ],
                [
                    'sku'          => 'LC',
                    'name'         => 'Level LC',
                    'description'  => "Level LC Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAC,
                ],
                [
                    'sku'          => 'LD',
                    'name'         => 'Level LD',
                    'description'  => "Level LD Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAC,
                ],
                [
                    'sku'          => 'LA',
                    'name'         => 'Level LA',
                    'description'  => "Level LA Description\nline of text",
                    'oem'          => $oemB,
                    'serviceGroup' => $groupBA,
                ],
            ],
            $toArray($levels),
        );
    }
}
