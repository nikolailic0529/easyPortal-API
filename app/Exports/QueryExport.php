<?php declare(strict_types = 1);

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;

class QueryExport implements FromCollection {
    use Exportable;

    public function __construct(protected Collection $collection) {
        $this->collection = $collection;
    }
    public function collection(): Collection {
        return $this->collection;
    }
}
