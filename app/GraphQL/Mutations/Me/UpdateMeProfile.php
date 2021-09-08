<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\User;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\User as UserType;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;

class UpdateMeProfile {
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
        $userType     = new UserType();
        $attributes   = $keycloakUser->attributes;
        foreach ($args['input'] as $property => $value) {
            switch ($property) {
                case 'first_name':
                    $userType->firstName = $value;
                    $user->given_name    = $value;
                    break;
                case 'last_name':
                    $userType->lastName = $value;
                    $user->family_name  = $value;
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
        $result = $this->client->updateUser($user->getKey(), $userType) && $user->save();
        return [
            'result' => $result,
        ];
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
