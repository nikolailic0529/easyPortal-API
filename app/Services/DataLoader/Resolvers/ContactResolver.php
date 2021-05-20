<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Contact;
use App\Models\Model;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use JetBrains\PhpStorm\Pure;

class ContactResolver extends Resolver {
    public function get(Model $model, ?string $name, ?string $phone, ?string $mail, Closure $factory = null): ?Contact {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($this->getUniqueKey($model, $name, $phone, $mail), $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Contact::query();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'unique' => new ClosureKey(function (Contact $contact): array {
                return $this->getUniqueKey($contact, $contact->name, $contact->phone_number, $contact->email);
            }),
        ];
    }

    /**
     * @return array{object_type: string, object_id: string, name: string|null, phone: string|null}
     */
    #[Pure]
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
