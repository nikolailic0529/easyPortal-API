<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Tasks;

use App\Services\Queue\Concerns\WithModelKey;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Database\Eloquent\Model;

/**
 * Recalculate one Model.
 */
class ModelRecalculate extends Recalculate implements ShouldBeUnique, ShouldBeUniqueUntilProcessing {
    /**
     * @use WithModelKey<Model>
     */
    use WithModelKey;

    public function displayName(): string {
        return 'ep-recalculator-model-recalculate';
    }

    /**
     * @inheritDoc
     */
    protected function getKeys(): array {
        return [$this->getKey()];
    }
}
