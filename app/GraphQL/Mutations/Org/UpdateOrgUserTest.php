<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\RealmUserNotFound;
use App\Services\KeyCloak\Client\Types\User as KeyCloakUser;
use Closure;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;

/**
 * @deprecated
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\UpdateOrgUser
 */
class UpdateOrgUserTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $prepare = null,
        Closure $dataFactory = null,
        Closure $clientFactory = null,
        bool $nullableData = false,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settings);

        $input = [];
        $map   = [];
        $file  = [];

        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        if ($dataFactory) {
            $input = $dataFactory($this, $organization, $user);

            if (array_key_exists('photo', $input)) {
                if (isset($input['photo'])) {
                    $map['0']       = ['variables.input.photo'];
                    $file['0']      = $input['photo'];
                    $input['photo'] = null;
                }
            }
        } else {
            User::factory()->create([
                'id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
            ]);
            $input['user_id'] = 'fd421bad-069f-491c-ad5f-5841aa9a9dfe';
        }

        $query = /** @lang GraphQL */
            'mutation updateOrgUser($input: UpdateOrgUserInput!){
                updateOrgUser(input: $input){
                    result
                }
          }';

        $operations = [
            'operationName' => 'updateOrgUser',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);
        if ($expected instanceof GraphQLSuccess) {
            /** @var \App\Models\User $updatedUser */
            $updatedUser = User::query()->whereKey($input['user_id'])->first();
            $this->assertNotNull($updatedUser);
            if ($nullableData) {
                $this->assertNull($updatedUser->given_name);
                $this->assertNull($updatedUser->family_name);
                $this->assertNull($updatedUser->title);
                $this->assertNull($updatedUser->academic_title);
                $this->assertNull($updatedUser->office_phone);
                $this->assertNull($updatedUser->mobile_phone);
                $this->assertNull($updatedUser->contact_email);
                $this->assertNull($updatedUser->job_title);
                $this->assertNull($updatedUser->photo);
                $this->assertNull($updatedUser->timezone);
                $this->assertNull($updatedUser->locale);
                $this->assertNull($updatedUser->homepage);
            } else {
                $this->assertEquals($updatedUser->given_name, $input['given_name']);
                $this->assertEquals($updatedUser->family_name, $input['family_name']);
                $this->assertEquals($updatedUser->title, $input['title']);
                $this->assertEquals($updatedUser->academic_title, $input['academic_title']);
                $this->assertEquals($updatedUser->office_phone, $input['office_phone']);
                $this->assertEquals($updatedUser->mobile_phone, $input['mobile_phone']);
                $this->assertEquals($updatedUser->contact_email, $input['contact_email']);
                $this->assertEquals($updatedUser->job_title, $input['job_title']);
                $this->assertEquals($updatedUser->timezone, $input['timezone']);
                $this->assertEquals($updatedUser->locale, $input['locale']);
                $this->assertEquals($updatedUser->homepage, $input['homepage']);
            }

            if (isset($input['team_id'])) {
                $organizationUser = $updatedUser->organizations->first();
                $this->assertNotNull($organizationUser);
                $this->assertEquals($organizationUser->team_id, $input['team_id']);
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $prepare  = static function (TestCase $test, ?Organization $organization, ?User $user): void {
            // Old Role
            $role = Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                'organization_id' => $organization->getKey(),
            ]);
            User::factory()
                ->hasOrganizations(1, [
                    'organization_id' => $organization->getKey(),
                    'role_id'         => $role->getKey(),
                ])
                ->create([
                    'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                    'type' => UserType::keycloak(),
                ]);

            // New Role
            Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                'organization_id' => $organization->getKey(),
            ]);

            // New Team
            Team::factory()->create([
                'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9ded',
                'name' => 'team1',
            ]);
        };
        $settings = [
            'ep.image.max_size' => 200,
            'ep.image.formats'  => ['jpg'],
        ];
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateOrgUser'),
            new OrganizationUserDataProvider('updateOrgUser', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                                    => [
                    new GraphQLSuccess('updateOrgUser', UpdateOrgUser::class),
                    $settings,
                    $prepare,
                    static function (TestCase $test, Organization $organization, User $user): array {
                        return [
                            'user_id'        => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'given_name'     => 'first',
                            'family_name'    => 'last',
                            'title'          => 'Mr',
                            'academic_title' => 'Professor',
                            'office_phone'   => '+1-202-555-0197',
                            'mobile_phone'   => '+1-202-555-0147',
                            'contact_email'  => 'test@gmail.com',
                            'job_title'      => 'Manger',
                            'photo'          => UploadedFile::fake()->create('photo.jpg', 100),
                            'homepage'       => 'dashboard',
                            'timezone'       => 'Europe/London',
                            'locale'         => 'en_GB',
                            'role_id'        => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'team_id'        => 'fd421bad-069f-491c-ad5f-5841aa9a9ded',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            ]));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->once()
                            ->andReturn(true);
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->once()
                            ->andReturn(true);
                    },
                ],
                'not me'                                => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    static function (TestCase $test, Organization $organization, User $user): array {
                        return [
                            'user_id' => $user->getKey(),
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->never();
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                ],
                'user not exists'                       => [
                    new GraphQLError('updateOrgUser', new RealmUserNotFound('id')),
                    $settings,
                    $prepare,
                    static function (): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andThrow(new RealmUserNotFound('id'));
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
                'Invalid user'                          => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    static function (): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->never();
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                ],
                'invalid request/Invalid contact email' => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    static function (): array {
                        return [
                            'user_id'       => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'contact_email' => 'wrong email',
                        ];
                    },
                ],
                'invalid request/Invalid photo size'    => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        'ep.image.max_size' => 100,
                        'ep.image.formats'  => ['jpg'],
                    ],
                    $prepare,
                    static function (TestCase $test): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'photo'   => UploadedFile::fake()->create('photo.jpg', 200),
                        ];
                    },
                ],
                'invalid request/Invalid photo format'  => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        'ep.image.max_size' => 200,
                        'ep.image.formats'  => ['jpg'],
                    ],
                    $prepare,
                    static function (TestCase $test): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'photo'   => UploadedFile::fake()->create('photo.png', 100),
                        ];
                    },
                ],
                'nullable data'                         => [
                    new GraphQLSuccess('updateOrgUser', updateOrgUser::class),
                    $settings,
                    $prepare,
                    static function (): array {
                        return [
                            'user_id'        => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'given_name'     => null,
                            'family_name'    => null,
                            'title'          => null,
                            'academic_title' => null,
                            'office_phone'   => null,
                            'mobile_phone'   => null,
                            'contact_email'  => null,
                            'job_title'      => null,
                            'photo'          => null,
                            'homepage'       => null,
                            'locale'         => null,
                            'timezone'       => null,
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser([
                                'id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            ]));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                    },
                    true,
                ],
                'Invalid Role'                          => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    static function (): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'role_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->never();
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                ],
                'Invalid Team'                          => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    static function (): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'team_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->never();
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                ],
                'Root User'                             => [
                    new GraphQLError('updateOrgUser', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    static function (TestCase $test, ?Organization $organization, ?User $user): void {
                        // Old Role
                        $role = Role::factory()->create([
                            'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                            'organization_id' => $organization->getKey(),
                        ]);
                        User::factory()
                            ->hasOrganizations(1, [
                                'organization_id' => $organization->getKey(),
                                'role_id'         => $role->getKey(),
                            ])
                            ->create([
                                'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                                'type' => UserType::local(),
                            ]);

                        // New Role
                        Role::factory()->create([
                            'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                            'organization_id' => $organization->getKey(),
                        ]);
                        // New Team
                        Team::factory()->create([
                            'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9ded',
                            'name' => 'team1',
                        ]);
                    },
                    static function (): array {
                        return [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dfz',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->never();
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                        $mock
                            ->shouldReceive('removeUserFromGroup')
                            ->never();
                        $mock
                            ->shouldReceive('addUserToGroup')
                            ->never();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
