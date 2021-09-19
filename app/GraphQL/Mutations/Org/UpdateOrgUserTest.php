<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\Role;
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
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;

/**
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
                $this->assertNull($updatedUser->department);
                $this->assertNull($updatedUser->job_title);
                $this->assertNull($updatedUser->photo);
                $this->assertNull($updatedUser->timezone);
                $this->assertNull($updatedUser->locale);
                $this->assertNull($updatedUser->homepage);
            } else {
                $this->assertEquals($updatedUser->given_name, $input['first_name']);
                $this->assertEquals($updatedUser->family_name, $input['last_name']);
                $this->assertEquals($updatedUser->title, $input['title']);
                $this->assertEquals($updatedUser->academic_title, $input['academic_title']);
                $this->assertEquals($updatedUser->office_phone, $input['office_phone']);
                $this->assertEquals($updatedUser->mobile_phone, $input['mobile_phone']);
                $this->assertEquals($updatedUser->contact_email, $input['contact_email']);
                $this->assertEquals($updatedUser->department, $input['department']);
                $this->assertEquals($updatedUser->job_title, $input['job_title']);
                $this->assertEquals($updatedUser->timezone, $input['timezone']);
                $this->assertEquals($updatedUser->locale, $input['locale']);
                $this->assertEquals($updatedUser->homepage, $input['homepage']);
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
            $updatedUser = User::factory()->create([
                'id'   => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                'type' => UserType::keycloak(),
            ]);

            // Old Role
            $role               = Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                'organization_id' => $organization->getKey(),
            ]);
            $updatedUser->roles = [$role];
            $updatedUser->save();

            // New Role
            Role::factory()->create([
                'id'              => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
                'organization_id' => $organization->getKey(),
            ]);
        };
        $settings = [
            'ep.image.max_size' => 200,
            'ep.image.formats'  => ['jpg'],
        ];
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateOrgUser'),
            new UserDataProvider('updateOrgUser'),
            new ArrayDataProvider([
                'ok'                                    => [
                    new GraphQLSuccess('updateOrgUser', UpdateOrgUser::class),
                    $settings,
                    $prepare,
                    static function (TestCase $test, Organization $organization, User $user): array {
                        return [
                            'user_id'        => 'fd421bad-069f-491c-ad5f-5841aa9a9dfe',
                            'first_name'     => 'first',
                            'last_name'      => 'last',
                            'title'          => 'Mr',
                            'academic_title' => 'Professor',
                            'office_phone'   => '+1-202-555-0197',
                            'mobile_phone'   => '+1-202-555-0147',
                            'contact_email'  => 'test@gmail.com',
                            'department'     => 'HR',
                            'job_title'      => 'Manger',
                            'photo'          => UploadedFile::fake()->create('photo.jpg', 100),
                            'homepage'       => 'dashboard',
                            'timezone'       => 'Europe/London',
                            'locale'         => 'en_GB',
                            'role_id'        => 'fd421bad-069f-491c-ad5f-5841aa9a9dff',
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
                            ->shouldReceive('removeUserToGroup')
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
                            ->shouldReceive('removeUserToGroup')
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
                            ->shouldReceive('removeUserToGroup')
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
                            'first_name'     => null,
                            'last_name'      => null,
                            'title'          => null,
                            'academic_title' => null,
                            'office_phone'   => null,
                            'mobile_phone'   => null,
                            'contact_email'  => null,
                            'department'     => null,
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
            ]),
        ))->getData();
    }
    // </editor-fold>
}
