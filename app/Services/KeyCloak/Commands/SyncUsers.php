<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Commands;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use Illuminate\Console\Command;

class SyncUsers extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:keycloak-sync-users';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Sync keycloak users';

    public function __construct(
        protected UsersIterator $iterator,
        protected Client $client,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void {
        $total = $this->client->usersCount();
        $bar   = $this->output->createProgressBar($total);
        $bar->start();
        $iterator = $this->iterator
            ->setLimit(100)
            ->setChunkSize(100)
            ->getIterator();
        foreach ($iterator as $item) {
            /** @var \App\Services\KeyCloak\Client\Types\User $item */
            $user = User::whereKey($item->id)->first();
            if (!$user) {
                $user                        = new User();
                $user->{$user->getKeyName()} = $item->id;
                $user->email                 = $item->email;
                $user->type                  = UserType::keycloak();
                $user->given_name            = $item->firstName;
                $user->family_name           = $item->lastName;
                $user->email_verified        = $item->emailVerified;
                $user->permissions           = [];
            }

            $organizations = [];
            $roles         = [];
            foreach ($item->groups as $group) {
                $organization = Organization::whereKey($group)->first();
                $role         = Role::whereKey($group)->first();
                if ($organization) {
                    $organizations[] = $organization;
                }

                if ($role) {
                    $roles[] = $role;
                }
            }
            $user->organizations = $organizations;
            $user->roles         = $roles;
            $user->save();
            $bar->advance();
        }
        $bar->finish();
    }
}
