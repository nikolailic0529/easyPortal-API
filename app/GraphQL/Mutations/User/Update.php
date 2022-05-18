<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User;

use App\Models\User;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Keycloak\Client\Client;
use App\Services\Keycloak\Client\Types\User as KeycloakUser;
use App\Services\Keycloak\Map;
use Illuminate\Http\UploadedFile;

class Update {
    public function __construct(
        protected Client $client,
        protected ModelDiskFactory $disks,
    ) {
        // empty
    }

    /**
     * @param array{input: array<mixed>} $args
     */
    public function __invoke(User $user, array $args): bool {
        return $this->update($user, new UpdateInput($args['input']));
    }

    public function update(User $user, UpdateInput $input): bool {
        $keycloakUser = $this->client->getUserById($user->getKey());
        $attributes   = $keycloakUser->attributes;
        $properties   = new KeycloakUser();

        foreach ($input as $property => $value) {
            switch ($property) {
                case 'given_name':
                    $properties->firstName = $value;
                    $user->given_name      = $value;
                    break;
                case 'family_name':
                    $properties->lastName = $value;
                    $user->family_name    = $value;
                    break;
                case 'locale':
                    $user->locale         = $value;
                    $attributes['locale'] = $value ? Map::getKeycloakLocale($value) : null;
                    break;
                case 'timezone':
                    $user->timezone = $value;
                    break;
                case 'photo':
                    $photo               = $this->store($user, $value);
                    $user->photo         = $photo;
                    $attributes['photo'] = [$photo];
                    break;
                default:
                    $user->setAttribute($property, $value);

                    $attributes[$property] = [$value];
                    break;
            }
        }

        $properties->attributes = $attributes;

        return $this->client->updateUser($user->getKey(), $properties)
            && $user->save();
    }

    protected function store(User $user, ?UploadedFile $file): ?string {
        $url = null;

        if ($file) {
            $disk = $this->disks->getDisk($user);
            $url  = $disk->url($disk->store($file));
        }

        return $url;
    }
}
