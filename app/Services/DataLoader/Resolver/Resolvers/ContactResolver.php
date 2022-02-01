<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Contact;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ContactResolver extends Resolver {
    public function get(Model $model, ?string $name, ?string $phone, ?string $mail, Closure $factory = null): ?Contact {
        return $this->resolve(
            $this->getUniqueKey($model, $name, $phone, $mail),
            $factory,
            $model->exists,
        );
    }

    /**
     * @param \App\Models\Contact
     *      |\Illuminate\Support\Collection<\App\Models\Contact>
     *      |array<\App\Models\Contact> $object
     */
    public function add(Contact|Collection|array $object): void {
        parent::put($object);
    }

    protected function getFindQuery(): ?Builder {
        return Contact::query();
    }

    public function getKey(Model $model): Key {
        return $model instanceof Contact
            ? $this->getCacheKey($this->getUniqueKey($model, $model->name, $model->phone_number, $model->email))
            : parent::getKey($model);
    }

    /**
     * @return array{object_type: string, object_id: string, name: string|null, phone: string|null}
     */
    protected function getUniqueKey(Model|Contact $model, ?string $name, ?string $phone, ?string $mail): array {
        return ($model instanceof Contact
                ? ['object_type' => $model->object_type, 'object_id' => $model->object_id]
                : ['object_type' => $model->getMorphClass(), 'object_id' => $model->getKey()]
            ) + [
                'name'         => $name,
                'phone_number' => $phone,
                'email'        => $mail,
            ];
    }
}
