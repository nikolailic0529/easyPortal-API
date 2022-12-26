<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\TextNormalizer;
use App\Services\DataLoader\Processors\Importer\Importers\OemsImporter\CellType;
use App\Services\DataLoader\Processors\Importer\Importers\OemsImporter\HeaderCell;
use App\Services\DataLoader\Processors\Importer\Importers\OemsImporter\ParsedRow;
use App\Services\DataLoader\Resolver\Resolvers\OemResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolver\Resolvers\ServiceLevelResolver;
use App\Services\I18n\I18n;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Cell;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Cell\Cell as SpreadsheetCell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

use function array_key_exists;
use function array_slice;
use function end;
use function explode;
use function implode;
use function is_array;
use function is_scalar;
use function mb_strtolower;
use function reset;

class OemsImporter implements OnEachRow, WithStartRow, WithEvents, SkipsEmptyRows {
    use Importable;

    /**
     * @var array<HeaderCell>
     */
    protected array $header = [];

    public function __construct(
        protected Application $app,
        protected OemResolver $oemResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
        protected I18n $i18n,
    ) {
        // empty
    }

    /**
     * @return array<string, ?string>
     */
    public function getLanguages(): array {
        return [
            'english' => null,
            'french'  => 'fr_FR',
            'german'  => 'de_DE',
            'italian' => 'it_IT',
        ];
    }

    /**
     * @return array<class-string,callable>
     */
    public function registerEvents(): array {
        return [
            BeforeSheet::class => function (BeforeSheet $event): void {
                $this->onBeforeSheet($event);
            },
            AfterSheet::class  => function (AfterSheet $event): void {
                $this->onAfterSheet($event);
            },
        ];
    }

    public function startRow(): int {
        // #1 - Header, we no need it.
        return 2;
    }

    /**
     * @protected
     */
    public function onRow(Row $row): void {
        // Empty?
        $parsed = $this->parse($row);

        if (!$parsed) {
            return;
        }

        // Import
        $oem          = $this->getOem($parsed);
        $serviceGroup = $this->getServiceGroup($oem, $parsed);
        $serviceLevel = $this->getServiceLevel($oem, $serviceGroup, $parsed);

        // Save translations (temporary implementation)
        $locales = [];
        $default = $this->app->getLocale();
        $helper  = new class() extends ServiceLevel {
            /**
             * @return array<string>
             */
            public function getModelTranslatableProperties(ServiceLevel $model): array {
                return $model->getTranslatableProperties();
            }

            public function getModelTranslatedPropertyKey(ServiceLevel $model, string $property): ?string {
                $keys = $model->getTranslatedPropertyKeys($property);
                $key  = reset($keys) ?: null;

                return $key;
            }
        };

        foreach ($helper->getModelTranslatableProperties($serviceLevel) as $property) {
            $translation = $helper->getModelTranslatedPropertyKey($serviceLevel, $property);

            if (!$translation) {
                continue;
            }

            $attribute                       = $serviceLevel->getAttribute($property);
            $locales[$default][$translation] = is_scalar($attribute) && $attribute
                ? (string) $attribute
                : null;

            foreach ($parsed->serviceLevel->translations ?? [] as $locale => $properties) {
                if (isset($properties[$property]) && $properties[$property]) {
                    $locales[$locale][$translation] = $properties[$property];
                }
            }
        }

        foreach ($locales as $locale => $translations) {
            $this->i18n->setTranslations($locale, $translations);
        }
    }

    protected function onBeforeSheet(BeforeSheet $event): void {
        // Parse header row
        $cells      = [];
        $sheet      = $event->sheet->getDelegate();
        $map        = [
            'A' => 'oem.key',
            'B' => 'serviceGroup.sku',
            'C' => 'serviceGroup.name',
            'D' => 'serviceLevel.sku',
        ];
        $languages  = $this->getLanguages();
        $properties = [
            'serviceLevel.name',
            'serviceLevel.description',
        ];
        $types      = [
            'serviceLevel.description' => CellType::text(),
        ];

        foreach ($sheet->getRowIterator() as $row) {
            $property = 0;

            foreach ($row->getCellIterator() as $index => $cell) {
                $value  = StringNormalizer::normalize($this->getCellValue($cell));
                $lang   = mb_strtolower((string) $value);
                $key    = null;
                $type   = null;
                $locale = null;

                if (isset($map[$index])) {
                    $key  = $map[$index];
                    $type = $types[$key] ?? null;
                } elseif (array_key_exists($lang, $languages)) {
                    $key  = $properties[$property];
                    $type = $types[$key] ?? null;
                    $code = $languages[$lang];

                    if ($code) {
                        $locale = $code;
                    }

                    if (isset($properties[$property + 1])) {
                        $property += 1;
                    } else {
                        $property = 0;
                    }
                } else {
                    // empty
                }

                if ($key) {
                    $cells[$index] = new HeaderCell($index, $key, $type, $locale);
                }
            }

            break;
        }

        // Save
        $this->header = $cells;
    }

    protected function onAfterSheet(AfterSheet $event): void {
        $this->header = [];
    }

    protected function getCellValue(SpreadsheetCell $cell): mixed {
        // Maatwebsite\Excel doesn't process merged cell...
        if ($cell->isInMergeRange() && !$cell->isMergeRangeValueCell()) {
            $coordinate = Coordinate::splitRange($cell->getMergeRange());

            do {
                $coordinate = reset($coordinate);
            } while (is_array($coordinate));

            $cell = $cell->getWorksheet()->getCell($coordinate);
        } else {
            // no action
        }

        // Get value
        $value = (new Cell($cell))->getValue();

        // Return
        return $value;
    }

    protected function parse(Row $row): ?ParsedRow {
        // Read into array
        $parsed = [];

        foreach ($row->getDelegate()->getCellIterator() as $index => $cell) {
            if (isset($this->header[$index])) {
                $header = $this->header[$index];
                $key    = $header->getKey();
                $value  = $this->getCellValue($cell);
                $locale = $header->getLocale();

                if ($locale) {
                    $path = explode('.', $key);
                    $name = end($path);
                    $key  = implode('.', array_slice($path, 0, -1));
                    $key  = "{$key}.translations.{$locale}.{$name}";
                }

                switch ($this->header[$index]->getType()) {
                    case CellType::text():
                        $value = TextNormalizer::normalize($value);
                        break;
                    default:
                        $value = StringNormalizer::normalize($value);
                        break;
                }

                Arr::set($parsed, $key, (string) $value);
            }
        }

        // Convert into object
        if ($parsed) {
            $parsed = new ParsedRow($parsed);
        } else {
            $parsed = null;
        }

        // Return
        return $parsed;
    }

    protected function getOem(ParsedRow $parsed): Oem {
        // Create
        $created = false;
        $factory = static function (Oem $oem) use (&$created, $parsed): Oem {
            $created   = !$oem->exists;
            $oem->key  = $parsed->oem->key;
            $oem->name = $parsed->oem->key;

            $oem->save();

            return $oem;
        };
        $oem     = $this->oemResolver->get($parsed->oem->key, static function () use ($factory): Oem {
            return $factory(new Oem());
        });

        // Update
        if (!$created) {
            $factory($oem);
        }

        // Return
        return $oem;
    }

    protected function getServiceGroup(Oem $oem, ParsedRow $parsed): ServiceGroup {
        // Create
        $created = false;
        $factory = static function (ServiceGroup $group) use (&$created, $oem, $parsed): ServiceGroup {
            $sku          = $parsed->serviceGroup->sku;
            $created      = !$group->exists;
            $group->key ??= "{$oem->getTranslatableKey()}/{$sku}";
            $group->oem   = $oem;
            $group->sku   = $sku;
            $group->name  = $parsed->serviceGroup->name;

            $group->save();

            return $group;
        };
        $group   = $this->serviceGroupResolver->get(
            $oem,
            $parsed->serviceGroup->sku,
            static function () use ($factory): ServiceGroup {
                return $factory(new ServiceGroup());
            },
        );

        // Update
        if (!$created) {
            $factory($group);
        }

        // Return
        return $group;
    }

    protected function getServiceLevel(Oem $oem, ServiceGroup $group, ParsedRow $parsed): ServiceLevel {
        // Create
        $created = false;
        $factory = static function (ServiceLevel $level) use (&$created, $oem, $group, $parsed): ServiceLevel {
            $sku                 = $parsed->serviceLevel->sku;
            $created             = !$level->exists;
            $level->key        ??= "{$group->getTranslatableKey()}/{$sku}";
            $level->oem          = $oem;
            $level->sku          = $sku;
            $level->name         = $parsed->serviceLevel->name ?? $sku;
            $level->description  = $parsed->serviceLevel->description ?? '';
            $level->serviceGroup = $group;

            $level->save();

            return $level;
        };
        $level   = $this->serviceLevelResolver->get(
            $oem,
            $group,
            $parsed->serviceLevel->sku,
            static function () use ($factory): ServiceLevel {
                return $factory(new ServiceLevel());
            },
        );

        // Update
        if (!$created) {
            $factory($level);
        }

        // Return
        return $level;
    }
}
