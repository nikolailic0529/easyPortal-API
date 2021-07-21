<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Importers\OemImporter\CellType;
use App\Services\DataLoader\Importers\OemImporter\HeaderCell;
use App\Services\DataLoader\Importers\OemImporter\ParsedRow;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\OemResolver;
use App\Services\DataLoader\Resolvers\ServiceGroupResolver;
use App\Services\DataLoader\Resolvers\ServiceLevelResolver;
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

use function array_flip;
use function array_key_exists;
use function array_map;
use function is_array;
use function mb_strtolower;
use function reset;

class OemsImporter implements OnEachRow, WithStartRow, WithEvents, SkipsEmptyRows {
    use Importable;

    /**
     * @var array<\App\Services\DataLoader\Importers\OemImporter\HeaderCell>
     */
    protected array $header = [];

    public function __construct(
        protected Normalizer $normalizer,
        protected OemResolver $oemResolver,
        protected ServiceGroupResolver $serviceGroupResolver,
        protected ServiceLevelResolver $serviceLevelResolver,
    ) {
        // empty
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
        $oem          = $this->oemResolver->get(
            $parsed->oem->key,
            static function () use ($parsed): Oem {
                $oem       = new Oem();
                $oem->key  = $parsed->oem->key;
                $oem->name = $parsed->oem->key;

                $oem->save();

                return $oem;
            },
        );
        $serviceGroup = $this->serviceGroupResolver->get(
            $oem,
            $parsed->serviceGroup->sku,
            static function () use ($oem, $parsed): ServiceGroup {
                $group       = new ServiceGroup();
                $group->oem  = $oem;
                $group->sku  = $parsed->serviceGroup->sku;
                $group->name = $parsed->serviceGroup->name;

                $group->save();

                return $group;
            },
        );

        $this->serviceLevelResolver->get(
            $oem,
            $serviceGroup,
            $parsed->serviceLevel->sku,
            static function () use ($oem, $serviceGroup, $parsed): ServiceLevel {
                $level               = new ServiceLevel();
                $level->oem          = $oem;
                $level->sku          = $parsed->serviceLevel->sku;
                $level->name         = $parsed->serviceLevel->name;
                $level->description  = $parsed->serviceLevel->description;
                $level->serviceGroup = $serviceGroup;

                $level->save();

                return $level;
            },
        );
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
        $languages  = [
            'english' => null,
            'french'  => 'fr',
            'german'  => 'de',
            'italian' => 'it',
        ];
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
                $value = $this->normalizer->string($this->getCellValue($cell));
                $lang  = mb_strtolower((string) $value);
                $key   = null;

                if (isset($map[$index])) {
                    $key = $map[$index];
                } elseif (array_key_exists($lang, $languages)) {
                    $code = $languages[$lang];
                    $key  = $properties[$property];

                    if ($code) {
                        // TODO [DataLoader] Translations + types
                        //      Should be, but temporary disabled.
                        //      $key .= "_{$code}";

                        $key = null;
                    }

                    if (isset($cells[$key])) {
                        $key = null;
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
                    $cells[$key] = $index;
                }
            }

            break;
        }

        // Save
        $this->header = array_map(static function (string $key) use ($types): HeaderCell {
            return new HeaderCell($key, $types[$key] ?? null);
        }, array_flip($cells));
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
                $key   = $this->header[$index]->getKey();
                $value = $this->getCellValue($cell);

                switch ($this->header[$index]->getType()) {
                    case CellType::text():
                        $value = $this->normalizer->text($value);
                        break;
                    default:
                        $value = $this->normalizer->string($value);
                        break;
                }

                Arr::set($parsed, $key, $value);
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
}
