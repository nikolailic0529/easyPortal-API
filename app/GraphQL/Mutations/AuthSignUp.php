<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\CurrentTenant;
use App\Models\User;
use App\Services\Auth0Management;
use Illuminate\Support\Str;

class AuthSignUp {
    protected Auth0Management $service;
    protected CurrentTenant   $tenant;

    public function __construct(Auth0Management $service, CurrentTenant $tenant) {
        $this->service = $service;
        $this->tenant  = $tenant;
    }

    /**
     * @see \App\GraphQL\Validators\Mutation\AuthSignUpValidator
     *
     * @param array<string, mixed> $args
     */
    public function __invoke(mixed $_, array $args): bool {
        $user                    = new User();
        $user->id                = Str::uuid()->toString();
        $user->given_name        = $args['given_name'];
        $user->family_name       = $args['family_name'];
        $user->email             = $args['email'];
        $user->phone             = $args['phone'];
        $user->email_verified_at = null;
        $user->blocked           = true;
        $user->permissions       = [];

        // Create Auth0 user
        $result = $this->service->createUser([
            'email'         => $args['email'],
            'blocked'       => true,
            'given_name'    => $args['given_name'],
            'family_name'   => $args['family_name'],
            'verify_email'  => true,
            'password'      => Str::random(20), // required
            'user_metadata' => [
                'phone'    => $args['phone'],
                'company'  => $args['company'],
                'reseller' => $args['reseller'],
            ],
            'app_metadata'  => [
                'uuid'   => $user->getKey(),
                'tenant' => $this->tenant->get()->getKey(),
            ],
        ]);

        // Update user
        $user->sub   = $result['user_id'];
        $user->photo = $result['picture'];

        // Save & return
        return $user->save();
    }
}
