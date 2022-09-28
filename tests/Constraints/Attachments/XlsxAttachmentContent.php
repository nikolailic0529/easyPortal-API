<?php declare(strict_types = 1);

namespace Tests\Constraints\Attachments;

use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Utils\Args;
use LastDragon_ru\LaraASP\Testing\Utils\WithTempFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SplFileInfo;

use function file_get_contents;
use function tap;

class XlsxAttachmentContent extends Response {
    use WithTempFile;

    public function __construct(SplFileInfo|string $content) {
        parent::__construct(
            new IsEqual(Args::content($content)),
        );
    }

    protected function isConstraintMatches(
        ResponseInterface $other,
        Constraint $constraint,
        bool $return = false,
    ): ?bool {
        $content = $this->toCsv($other->getBody());
        $matches = $constraint->evaluate($content, '', $return);

        return $matches;
    }

    protected function toCsv(StreamInterface $xlsx): string {
        $source = $this->getTempFile((string) $xlsx);
        $target = $this->getTempFile();
        $reader = new XlsxReader();
        $writer = new CsvWriter(tap(new CsvOptions(), static function (CsvOptions $options): void {
            $options->SHOULD_ADD_BOM = false;
        }));

        try {
            $reader->open($source->getPathname());
            $writer->openToFile($target->getPathname());

            foreach ($reader->getSheetIterator() as $sheet) {
                $writer->addRow(Row::fromValues([
                    "Sheet #{$sheet->getIndex()}: {$sheet->getName()}",
                ]));

                foreach ($sheet->getRowIterator() as $row) {
                    $writer->addRow($row);
                }
            }
        } finally {
            $reader->close();
            $writer->close();
        }

        return (string) file_get_contents($target->getPathname());
    }
}
