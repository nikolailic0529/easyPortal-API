<?php declare(strict_types = 1);

use App\Models\Data\Coverage;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Services\DataLoader\Normalizers\NameNormalizer;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    public function up(): void {
        $this->updateTypes();
        $this->updateStatuses();
        $this->updateCoverages();
    }

    public function down(): void {
        // empty
    }

    protected function updateTypes(): void {
        $types = Type::query()->get();

        foreach ($types as $type) {
            $type->name = NameNormalizer::normalize($type->name);

            $type->save();
        }
    }

    protected function updateStatuses(): void {
        $statuses = Status::query()->get();

        foreach ($statuses as $status) {
            $status->name = NameNormalizer::normalize($status->name);

            $status->save();
        }
    }

    protected function updateCoverages(): void {
        $coverages = Coverage::query()->get();

        foreach ($coverages as $coverage) {
            $coverage->name = NameNormalizer::normalize($coverage->name);

            $coverage->save();
        }
    }
};
