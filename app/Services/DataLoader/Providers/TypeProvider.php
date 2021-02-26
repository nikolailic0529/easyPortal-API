<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Model;
use App\Models\Type;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class TypeProvider extends Provider {
    public function get(Model $model, string $type, Closure $factory): Type {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($model, $type), $factory);
    }

    protected function getInitialQuery(): ?Builder {
        return Type::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
                'type' => new ClosureKey(function (Type $type): array {
                    return $this->getUniqueKey($type->object_type, $type->type);
                }),
            ] + parent::getKeyRetrievers();
    }

    /**
     * @return array{model: string, type: string}
     */
    #[Pure]
    protected function getUniqueKey(Model|string $model, string $type): array {
        return [
            'object_type' => $model instanceof Model ? $model->getMorphClass() : $model,
            'type'        => $type,
        ];
    }
}
