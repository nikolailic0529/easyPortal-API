<?php declare(strict_types = 1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QueryExport implements FromCollection, WithStyles {
    use Exportable;

    public function __construct(
        protected Collection $collection,
    ) {
        // empty
    }

    public function collection(): Collection {
        return $this->collection;
    }

    /**
     * @return array<mixed>
     */
    public function styles(Worksheet $sheet): array {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }
}
