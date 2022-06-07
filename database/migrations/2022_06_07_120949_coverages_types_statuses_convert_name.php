<?php declare(strict_types = 1);

use App\Models\Coverage;
use App\Models\Status;
use App\Models\Type;
use App\Services\DataLoader\Normalizer\Normalizer;
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
        $normalizer = app()->make(Normalizer::class);
        $types      = Type::query()->get();

        foreach ($types as $type) {
            $type->name = $normalizer->name($type->key);

            $type->save();
        }
    }

    protected function updateStatuses(): void {
        $normalizer = app()->make(Normalizer::class);
        $statuses   = Status::query()->get();

        foreach ($statuses as $status) {
            $status->name = $normalizer->name($status->key);

            $status->save();
        }
    }

    protected function updateCoverages(): void {
        $normalizer = app()->make(Normalizer::class);
        $coverages  = Coverage::query()->get();

        foreach ($coverages as $coverage) {
            $coverage->name = $normalizer->name($coverage->key);

            $coverage->save();
        }
    }
};
