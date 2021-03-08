<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Contact;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

/**
 * @internal
 */
class ContactProvider extends Provider {
    public function get(Model $model, ?string $name, ?string $phone, Closure $factory = null): ?Contact {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($model, $name, $phone), $factory);
    }

    /**
     * @param array{object_type: string, object_id: string, name: string|null, phone: string|null} $key
     */
    protected function getFindQuery(mixed $key): ?Builder {
        return Contact::query()
            ->where('object_type', '=', $key['object_type'])
            ->where('object_id', '=', $key['object_id'])
            ->where('name', '=', $key['name'])
            ->where('phone_number', '=', $key['phone_number']);
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey(function (Contact $contact): array {
                return $this->getUniqueKey($contact, $contact->name, $contact->phone_number);
            }),
        ];
    }

    /**
     * @return array{object_type: string, object_id: string, name: string|null, phone: string|null}
     */
    #[Pure]
    protected function getUniqueKey(Model|Contact $model, ?string $name, ?string $phone): array {
        return ($model instanceof Contact
                ? ['object_type' => $model->object_type, 'object_id' => $model->object_id]
                : ['object_type' => $model->getMorphClass(), 'object_id' => $model->getKey()]
            ) + [
                'name'         => $name,
                'phone_number' => $phone,
            ];
    }
}
