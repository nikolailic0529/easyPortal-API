<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKeys;
use Illuminate\Database\Eloquent\Model;

class ModelsRecalculate extends Recalculate {
    /**
     * @use WithModelKeys<Model>
     */
    use WithModelKeys;

    public function displayName(): string {
        return 'ep-recalculator-models-recalculate';
    }
}
