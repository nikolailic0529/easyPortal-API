<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Me;

use App\Models\Organization;
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
 * @deprecated
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Me\UpdateMe
 */
class UpdateMeTest extends TestCase {
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

        if ($dataFactory) {
            $input = $dataFactory($this, $organization, $user);

            if (array_key_exists('photo', $input)) {
                if (isset($input['photo'])) {
                    $map['0']       = ['variables.input.photo'];
                    $file['0']      = $input['photo'];
                    $input['photo'] = null;
                }
            }
        }

        $query = /** @lang GraphQL */
            'mutation updateMe($input: UpdateMeInput!){
            updateMe(input: $input){
              result
            }
          }';

        $operations = [
            'operationName' => 'updateMe',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);
        if ($expected instanceof GraphQLSuccess) {
            if ($nullableData) {
                $this->assertNull($user->given_name);
                $this->assertNull($user->family_name);
                $this->assertNull($user->title);
                $this->assertNull($user->academic_title);
                $this->assertNull($user->office_phone);
                $this->assertNull($user->mobile_phone);
                $this->assertNull($user->contact_email);
                $this->assertNull($user->job_title);
                $this->assertNull($user->photo);
                $this->assertNull($user->homepage);
                $this->assertNull($user->locale);
                $this->assertNull($user->timezone);
            } else {
                $this->assertEquals($user->given_name, $input['given_name']);
                $this->assertEquals($user->family_name, $input['family_name']);
                $this->assertEquals($user->title, $input['title']);
                $this->assertEquals($user->academic_title, $input['academic_title']);
                $this->assertEquals($user->office_phone, $input['office_phone']);
                $this->assertEquals($user->mobile_phone, $input['mobile_phone']);
                $this->assertEquals($user->contact_email, $input['contact_email']);
                $this->assertEquals($user->job_title, $input['job_title']);
                $this->assertEquals($user->homepage, $input['homepage']);
                $this->assertEquals($user->locale, $input['locale']);
                $this->assertEquals($user->timezone, $input['timezone']);
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
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateMe'),
            new UserDataProvider('updateMe'),
            new ArrayDataProvider([
                'ok'                                    => [
                    new GraphQLSuccess('updateMe', UpdateMe::class),
                    [],
                    static function (TestCase $test, Organization $organization, User $user): array {
                        $user->save();

                        return [
                            'given_name'     => 'first',
                            'family_name'    => 'last',
                            'title'          => 'Mr',
                            'academic_title' => 'Professor',
                            'office_phone'   => '+1-202-555-0197',
                            'mobile_phone'   => '+1-202-555-0147',
                            'contact_email'  => 'test@gmail.com',
                            'job_title'      => 'Manger',
                            'photo'          => UploadedFile::fake()->create('photo.jpg', 200),
                            'homepage'       => 'dashboard',
                            'timezone'       => 'Europe/London',
                            'locale'         => 'en_GB',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser(['attributes' => []]));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                    },
                ],
                'user not exists'                       => [
                    new GraphQLError('updateMe', new RealmUserNotFound('id')),
                    [],
                    static function (): array {
                        return [
                            'given_name'  => 'first',
                            'family_name' => 'last',
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
                'invalid request/Invalid contact email' => [
                    new GraphQLError('updateMe', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [],
                    static function (): array {
                        return [
                            'contact_email' => 'wrong email',
                        ];
                    },
                ],
                'invalid request/Invalid photo size'    => [
                    new GraphQLError('updateMe', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        'ep.image.max_size' => 100,
                        'ep.image.formats'  => ['jpg'],
                    ],
                    static function (TestCase $test): array {
                        return [
                            'photo' => UploadedFile::fake()->create('photo.jpg', 200),
                        ];
                    },
                ],
                'invalid request/Invalid photo format'  => [
                    new GraphQLError('updateMe', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [
                        'ep.image.max_size' => 200,
                        'ep.image.formats'  => ['jpg'],
                    ],
                    static function (TestCase $test): array {
                        return [
                            'photo' => UploadedFile::fake()->create('photo.png', 100),
                        ];
                    },
                ],
                'nullable data'                         => [
                    new GraphQLSuccess('updateMe', updateMe::class),
                    [],
                    static function (): array {
                        return [
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
                            'timezone'       => null,
                            'locale'         => null,
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new KeyCloakUser(['attributes' => []]));
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
