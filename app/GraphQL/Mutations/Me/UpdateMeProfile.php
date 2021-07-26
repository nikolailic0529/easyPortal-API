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
        // Prepare
        $user         = $this->auth->user();
        $keycloakUser = $this->client->getUserById($user->getKey());
        $userType     = $this->prepare($user, $keycloakUser, $args['input']);
        $result       = $this->client->updateUser($user->getKey(), $userType);

        // Return
        return [
            'result' => $result,
        ];
    }

    /**
     * @param array<mixed> $properties
     */
    protected function prepare(User $user, UserType $keycloakUser, array $properties): UserType {
        $userType   = new UserType();
        $attributes = $keycloakUser->attributes;
        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'first_name':
                    $userType->firstName = $value;
                    break;
                case 'last_name':
                    $userType->lastName = $value;
                    break;
                case 'photo':
                    $attributes['photo'] = [$this->store($user, $value)];
                    break;
                default:
                    $attributes[$property] = [$value];
                    break;
            }
        }
        $userType->attributes = $attributes;

        return $userType;
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
