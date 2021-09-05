<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\User;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\UploadedFile;

use function is_null;

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
        $user = $this->auth->user();
        foreach ($args['input'] as $property => $value) {
            switch ($property) {
                case 'first_name':
                    if (!is_null($value)) {
                        $user->given_name = $value;
                    }
                    break;
                case 'last_name':
                    if (!is_null($value)) {
                        $user->family_name = $value;
                    }
                    break;
                case 'photo':
                    $user->photo = $this->store($user, $value);
                    break;
                default:
                    $user->{$property} = $value;
                    break;
            }
        }
        return [
            'result' => $user->save(),
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
