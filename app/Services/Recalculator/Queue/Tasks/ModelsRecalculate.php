<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKeys;

class ModelsRecalculate extends Recalculate {
    /**
     * @use WithModelKeys<\Illuminate\Database\Eloquent\Model>
     */
    use WithModelKeys;

    public function displayName(): string {
        return 'ep-recalculator-models-recalculate';
    }
}
