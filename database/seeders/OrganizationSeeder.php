<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Enums\OrganizationType;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organization\RootOrganization;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Hash;
use LastDragon_ru\LaraASP\Migrator\Seeders\SmartSeeder;

class OrganizationSeeder extends SmartSeeder {
    public function seed(): void {
        // Root organization
        $root               = Container::getInstance()->make(RootOrganization::class);
        $organization       = new Organization();
        $organization->id   = $root->getKey();
        $organization->name = 'Root Organization';
        $organization->type = OrganizationType::reseller();
        $organization->save();

        // Root user
        $user                 = new User();
        $user->type           = UserType::local();
        $user->password       = Hash::make('1234567890');
        $user->given_name     = 'Root';
        $user->family_name    = 'User';
        $user->email          = 'fakharanwar@hotmail.com';
        $user->email_verified = true;
        $user->phone          = '';
        $user->phone_verified = false;
        $user->permissions    = [];
        $user->organization   = $organization;
        $user->enabled        = true;
        $user->save();
    }
}
