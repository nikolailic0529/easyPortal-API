<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\User;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as UserType;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;

class UpdateMe {
    public function __construct(
        protected AuthManager $auth,
        protected Client $client,
        protected ModelDiskFactory $disks,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $user         = $this->auth->user();
        $keycloakUser = $this->client->getUserById($user->getKey());
        $result       = $this->updateUser($user, $keycloakUser, $args['input']);
        return [
            'result' => $result && $user->save(),
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    public function updateUser(User $user, UserType $keycloakUser, array $input): bool {
        $userType   = new UserType();
        $attributes = $keycloakUser->attributes;
        foreach ($input as $property => $value) {
            switch ($property) {
                case 'given_name':
                    $userType->firstName = $value;
                    $user->given_name    = $value;
                    break;
                case 'family_name':
                    $userType->lastName = $value;
                    $user->family_name  = $value;
                    break;
                case 'homepage':
                    $user->homepage = $value;
                    break;
                case 'locale':
                    $user->locale = $value;
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
                    $user->{$property}     = $value;
                    $attributes[$property] = [$value];
                    break;
            }
        }
        $userType->attributes = $attributes;

        // Update Keycloak
        return $this->client->updateUser($user->getKey(), $userType);
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
