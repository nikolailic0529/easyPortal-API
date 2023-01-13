<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\Filesystem\Disks\AppDisk;
use App\Services\I18n\Storages\AppTranslations;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function array_map;
use function array_merge;

/**
 * @internal
 * @covers \App\Services\DataLoader\Processors\Importer\Importers\OemsImporter
 */
class OemsImporterTest extends TestCase {
    public function testProcess(): void {
        // Helpers
        $defaultLocaleStorage = new AppTranslations($this->app->make(AppDisk::class), $this->app->getLocale());
        $customLocaleStorage  = new AppTranslations($this->app->make(AppDisk::class), 'fr_FR');
        $toArray              = static function (Collection $models): array {
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
        self::assertEquals(0, Oem::query()->count());
        self::assertEquals(0, ServiceGroup::query()->count());
        self::assertEquals(0, ServiceLevel::query()->count());
        self::assertEmpty($customLocaleStorage->load());

        // Existing objects should be updated
        $oem   = Oem::factory()->create([
            'key'  => 'ABC',
            'name' => 'should be updated',
        ]);
        $group = ServiceGroup::factory()->create([
            'key'    => 'ABC/GA',
            'sku'    => 'GA',
            'name'   => 'should be updated',
            'oem_id' => $oem,
        ]);

        ServiceLevel::factory()->create([
            'key'              => 'ABC/GA/LA',
            'sku'              => 'LA',
            'name'             => 'should be updated',
            'oem_id'           => $oem,
            'service_group_id' => $group,
        ]);

        // Add Transactions
        $defaultLocaleStorage->save([
            'models.ServiceLevel.unknown.name'          => 'Unknown Name',
            'models.ServiceLevel.unknown.description'   => 'Unknown Description',
            'models.ServiceLevel.ABC/GA/LA.name'        => 'Level LA Name',
            'models.ServiceLevel.ABC/GA/LA.description' => 'Level LA Description',
        ]);
        $customLocaleStorage->save([
            'models.ServiceLevel.unknown.name'          => 'Unknown Name',
            'models.ServiceLevel.unknown.description'   => 'Unknown Description',
            'models.ServiceLevel.ABC/GA/LA.name'        => 'Level LA Name',
            'models.ServiceLevel.ABC/GA/LA.description' => 'Level LA Description',
        ]);

        // Run
        $this->app->make(OemsImporter::class)->import($this->getTestData()->file('.xlsx'));

        // Oems
        $oems = Oem::query()->get()->keyBy('id');
        $oemA = [
            'key'  => 'ABC',
            'name' => 'ABC',
        ];
        $oemB = [
            'key'  => 'CBA',
            'name' => 'CBA',
        ];

        self::assertCount(2, $oems);
        self::assertEqualsCanonicalizing([$oemA, $oemB], $toArray($oems));

        // Service Groups
        $groups  = ServiceGroup::query()
            ->with('oem')
            ->get()
            ->keyBy('id');
        $groupAA = [
            'key'  => 'ABC/GA',
            'sku'  => 'GA',
            'name' => 'Group A',
            'oem'  => $oemA,
        ];
        $groupAB = [
            'key'  => 'ABC/GB',
            'sku'  => 'GB',
            'name' => 'Group B',
            'oem'  => $oemA,
        ];
        $groupAC = [
            'key'  => 'ABC/GC',
            'sku'  => 'GC',
            'name' => 'Group C',
            'oem'  => $oemA,
        ];
        $groupBA = [
            'key'  => 'CBA/GA',
            'sku'  => 'GA',
            'name' => 'Group A',
            'oem'  => $oemB,
        ];

        self::assertCount(4, $groups);
        self::assertEqualsCanonicalizing(
            [$groupAA, $groupAB, $groupAC, $groupBA],
            $toArray($groups),
        );

        // Service Levels
        $levels = ServiceLevel::query()
            ->with('oem')
            ->with('serviceGroup')
            ->with('serviceGroup.oem')
            ->get()
            ->keyBy('id');

        self::assertCount(5, $levels);
        self::assertEqualsCanonicalizing(
            [
                [
                    'key'          => "{$groupAA['key']}/LA",
                    'sku'          => 'LA',
                    'name'         => 'Level LA',
                    'description'  => "Level LA Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAA,
                ],
                [
                    'key'          => "{$groupAB['key']}/LB",
                    'sku'          => 'LB',
                    'name'         => 'Level LB',
                    'description'  => "Level LB Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAB,
                ],
                [
                    'key'          => "{$groupAC['key']}/LC",
                    'sku'          => 'LC',
                    'name'         => 'Level LC',
                    'description'  => "Level LC Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAC,
                ],
                [
                    'key'          => "{$groupAC['key']}/LD",
                    'sku'          => 'LD',
                    'name'         => 'Level LD',
                    'description'  => "Level LD Description\nline of text",
                    'oem'          => $oemA,
                    'serviceGroup' => $groupAC,
                ],
                [
                    'key'          => "{$groupBA['key']}/LA",
                    'sku'          => 'LA',
                    'name'         => 'Level LA',
                    'description'  => "Level LA Description\nline of text",
                    'oem'          => $oemB,
                    'serviceGroup' => $groupBA,
                ],
            ],
            $toArray($levels),
        );

        // Default Locale Translations
        self::assertEquals(
            [
                'models.ServiceLevel.unknown.name'          => 'Unknown Name',
                'models.ServiceLevel.unknown.description'   => 'Unknown Description',
                'models.ServiceLevel.ABC/GA/LA.name'        => 'Level LA',
                'models.ServiceLevel.ABC/GA/LA.description' => "Level LA Description\nline of text",
                'models.ServiceLevel.ABC/GB/LB.name'        => 'Level LB',
                'models.ServiceLevel.ABC/GB/LB.description' => "Level LB Description\nline of text",
                'models.ServiceLevel.ABC/GC/LC.name'        => 'Level LC',
                'models.ServiceLevel.ABC/GC/LD.name'        => 'Level LD',
                'models.ServiceLevel.ABC/GC/LD.description' => "Level LD Description\nline of text",
                'models.ServiceLevel.CBA/GA/LA.name'        => 'Level LA',
                'models.ServiceLevel.CBA/GA/LA.description' => "Level LA Description\nline of text",
                'models.ServiceLevel.ABC/GC/LC.description' => "Level LC Description\nline of text",
            ],
            $defaultLocaleStorage->load(),
        );

        // Custom Locale Translations
        self::assertEquals(
            [
                'models.ServiceLevel.unknown.name'          => 'Unknown Name',
                'models.ServiceLevel.unknown.description'   => 'Unknown Description',
                'models.ServiceLevel.ABC/GA/LA.name'        => 'Level LA French',
                'models.ServiceLevel.ABC/GA/LA.description' => "Level LA Description French\nline of text",
                'models.ServiceLevel.ABC/GB/LB.name'        => 'Level LB French',
                'models.ServiceLevel.ABC/GB/LB.description' => "Level LB Description French\nline of text",
                'models.ServiceLevel.ABC/GC/LC.name'        => 'Level LC French',
                'models.ServiceLevel.ABC/GC/LD.name'        => 'Level LD French',
                'models.ServiceLevel.ABC/GC/LD.description' => "Level LD Description French\nline of text",
                'models.ServiceLevel.CBA/GA/LA.name'        => 'Level LA French',
                'models.ServiceLevel.CBA/GA/LA.description' => "Level LA Description French\nline of text",
            ],
            $customLocaleStorage->load(),
        );
    }

    public function testProcessNoEnglish(): void {
        // Pretest
        self::assertModelsCount([
            Oem::class          => 0,
            ServiceGroup::class => 0,
            ServiceLevel::class => 0,
        ]);

        // Run
        $this->app->make(OemsImporter::class)->import(
            $this->getTestData()->file('~no-english.xlsx'),
        );

        // Service Levels
        $levels = ServiceLevel::query()
            ->with('oem')
            ->with('serviceGroup')
            ->with('serviceGroup.oem')
            ->orderBy('sku')
            ->get()
            ->map(static function (ServiceLevel $level): array {
                return [
                    'sku'         => $level->sku,
                    'name'        => $level->name,
                    'description' => $level->description,
                ];
            })
            ->all();

        self::assertEquals(
            [
                [
                    'sku'         => 'LA',
                    'name'        => 'LA',
                    'description' => '',
                ],
                [
                    'sku'         => 'LB',
                    'name'        => 'LB',
                    'description' => '',
                ],
                [
                    'sku'         => 'LC',
                    'name'        => 'LC',
                    'description' => '',
                ],
                [
                    'sku'         => 'LD',
                    'name'        => 'LD',
                    'description' => '',
                ],
            ],
            $levels,
        );
    }
}
