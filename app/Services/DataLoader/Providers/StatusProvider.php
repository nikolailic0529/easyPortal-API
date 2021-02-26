<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Model;
use App\Models\Status;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class StatusProvider extends Provider {
    public function get(Model $model, string $key, Closure $factory): Status {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($model, $key), $factory);
    }

    protected function getInitialQuery(): ?Builder {
        return Status::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'key' => new ClosureKey(function (Status $type): array {
                return $this->getUniqueKey($type->object_type, $type->key);
            }),
        ];
    }

    /**
     * @return array{model: string, type: string}
     */
    #[Pure]
    protected function getUniqueKey(Model|string $model, string $key): array {
        return [
            'object_type' => $model instanceof Model ? $model->getMorphClass() : $model,
            'key'         => $key,
        ];
    }
}
