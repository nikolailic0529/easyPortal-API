<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\CurrentTenant;
use App\Http\Requests\Auth\SignupRequest;
use App\Http\Resources\Auth\SignupResource;
use App\Http\Resources\RedirectResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth0Management;
use Auth0\Login\Auth0Service;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Spa\Http\Resources\Scalar\NullResource;

use function sprintf;

class AuthController extends Controller {
    /**
     * Return info about current user or `null` for guests.
     */
    public function info(Request $request): UserResource|NullResource {
        return $request->user()
            ? new UserResource($request->user())
            : new NullResource();
    }

    /**
     * Returns the link where guest should be redirected to sign into.
     */
    public function signin(Auth0Service $service): RedirectResource {
        $redirect = $service->login(null, null, [
            ['scope' => 'openid profile email'],
        ]);

        return new RedirectResource($redirect->getTargetUrl());
    }

    /**
     * Destroys the session and returns the link where the guest should be
     * redirected to sign out.
     */
    public function signout(Repository $config, Request $request): RedirectResource {
        // TODO [auth] Probably this method should not return Redirect

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $logoutUrl = sprintf(
            'https://%s/v2/logout?client_id=%s&returnTo=%s',
            $config->get('laravel-auth0.domain'),
            $config->get('laravel-auth0.client_id'),
            $config->get('app.url'),
        );

        return new RedirectResource($logoutUrl);
    }

    /**
     * Creates new user profile.
     */
    public function signup(Auth0Management $auth0, CurrentTenant $tenant, SignupRequest $request): SignupResource {
        // Create local User
        $data                    = $request->validated();
        $user                    = new User();
        $user->id                = Str::uuid()->toString();
        $user->given_name        = $data['given_name'];
        $user->family_name       = $data['family_name'];
        $user->email             = $data['email'];
        $user->phone             = $data['phone'];
        $user->email_verified_at = null;
        $user->blocked           = true;
        $user->permissions       = [];

        // Create Auth0 user
        $result = $auth0->createUser([
            'email'         => $data['email'],
            'blocked'       => true,
            'connection'    => 'Username-Password-Authentication', // FIXME [auth0] Specify connection
            'given_name'    => $data['given_name'],
            'family_name'   => $data['family_name'],
            'verify_email'  => true,
            'password'      => Str::random(20), // required
            'user_metadata' => [
                'phone'    => $data['phone'],
                'company'  => $data['company'],
                'reseller' => $data['reseller'],
            ],
            'app_metadata'  => [
                'uuid'   => $user->getKey(),
                'tenant' => $tenant->get()->getKey(),
            ],
        ]);

        // Update user
        $user->sub   = $result['user_id'];
        $user->photo = $result['picture'];
        $user->save();

        // Return
        return new SignupResource($user);
    }
}
