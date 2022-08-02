<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Services\DataLoader\Importer\Importers\OemsImporter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Localizable;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;

use function pathinfo;
use function reset;

use const PATHINFO_EXTENSION;

class OemsController extends Controller {
    use Localizable;

    public function __construct(
        protected Application $app,
        protected ResponseFactory $factory,
        protected OemsImporter $importer,
    ) {
        // empty
    }

    public function __invoke(Oem $oem): Response {
        $filename  = "{$oem->name}.xlsx";
        $mimetypes = (new MimeTypes())->getMimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
        $mimetype  = reset($mimetypes);
        $headers   = [
            'Content-Type' => "{$mimetype}; charset=UTF-8",
        ];

        $oem->loadMissing('groups.levels');

        return $this->factory->streamDownload(function () use ($oem): void {
            $column  = 0;
            $options = new Options();
            $options->setColumnWidth(15, ++$column);
            $options->setColumnWidth(20, ++$column);
            $options->setColumnWidth(35, ++$column);
            $options->setColumnWidth(20, ++$column);

            $style  = (new Style())->setFontBold();
            $header = Row::fromValues([
                'Vendor',
                'Service Group SKU',
                'Service Group Description',
                'Service Level SKU',
            ])->setStyle($style);

            foreach ($this->importer->getLanguages() as $language => $locale) {
                // Prepare
                $languageName = Str::title($language);
                $languageCell = Cell::fromValue($languageName);

                // Merge
                $options->mergeCells($column, 1, $column + 1, 1);

                // Name
                $header->addCell($languageCell);
                $options->setColumnWidth(35, ++$column);

                // Description
                $header->addCell($languageCell);
                $options->setColumnWidth(35, ++$column);
            }

            $writer = new Writer($options);
            $writer->openToFile('php://output');
            $writer->addRow($header);

            $this->writeOem($writer, $options, $oem);

            $writer->close();
        }, $filename, $headers);
    }

    protected function writeOem(Writer $writer, Options $options, Oem $oem): void {
        // Add
        $rows   = $writer->getWrittenRowCount();
        $groups = $oem->loadMissing('groups')->groups->sortBy(
            static function (ServiceGroup $group): string {
                return $group->sku;
            },
        );

        foreach ($groups as $group) {
            $this->writeGroup($writer, $options, $oem, clone $group);
        }

        // Merge
        $this->mergeRows($writer, $options, 0, $rows);
    }

    protected function writeGroup(Writer $writer, Options $options, Oem $oem, ServiceGroup $group): void {
        $rows   = $writer->getWrittenRowCount();
        $levels = $group->loadMissing('levels')->levels->sortBy(
            static function (ServiceLevel $level): string {
                return $level->sku;
            },
        );

        foreach ($levels as $level) {
            $this->writeLevel($writer, $oem, $options, $group, $level);
        }

        $this->mergeRows($writer, $options, 1, $rows);
        $this->mergeRows($writer, $options, 2, $rows);
    }

    protected function writeLevel(
        Writer $writer,
        Oem $oem,
        Options $options,
        ServiceGroup $group,
        ServiceLevel $level,
    ): void {
        $languages = $this->importer->getLanguages();
        $default   = $this->app->getLocale();
        $style     = (new Style())->setCellVerticalAlignment(
            CellVerticalAlignment::TOP,
        );
        $row       = Row::fromValues([
            $oem->name,
            $group->sku,
            $group->name,
            $level->sku,
        ]);

        foreach ($languages as $locale) {
            $this->withLocale($locale ?? $default, static function () use ($level, $row): void {
                $row->addCell(Cell::fromValue($level->getTranslatedProperty('name')));
                $row->addCell(Cell::fromValue($level->getTranslatedProperty('description')));
            });
        }

        $writer->addRow($row->setStyle($style));
    }

    protected function mergeRows(Writer $writer, Options $options, int $column, int $rows): void {
        $added = $writer->getWrittenRowCount() - $rows;

        if ($rows > 0 && $added > 0 && $column >= 0) {
            $sheet             = $writer->getCurrentSheet()->getIndex();
            $topLeftColumn     = $column;
            $topLeftRow        = $rows + 1;
            $bottomRightColumn = $column;
            $bottomRightRow    = $topLeftRow + $added - 1;

            $options->mergeCells($topLeftColumn, $topLeftRow, $bottomRightColumn, $bottomRightRow, $sheet);
        }
    }
}
