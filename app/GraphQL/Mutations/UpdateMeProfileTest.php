<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Exceptions\UserDoesntExists;
use App\Services\KeyCloak\Client\Types\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateMeProfile
 */
class UpdateMeProfileTest extends TestCase {
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
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));
        $this->setSettings($settings);

        Storage::fake();

        $input = [];
        $data  = [];
        $map   = [];
        $file  = [];

        if ($dataFactory) {
            $data  = $dataFactory($this);
            $input = $data;

            if (array_key_exists('photo', $input)) {
                if (isset($input['photo'])) {
                    $map['0']       = ['variables.input.photo'];
                    $file['0']      = $input['photo'];
                    $input['photo'] = null;
                }
            }
        }

        $query = /** @lang GraphQL */
            'mutation updateMeProfile($input: UpdateMeProfileInput!){
            updateMeProfile(input: $input){
              result
            }
          }';

        $operations = [
            'operationName' => 'updateMeProfile',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];


        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
        }

        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateMeProfile'),
            new AuthUserDataProvider('updateMeProfile'),
            new ArrayDataProvider([
                'ok'                                    => [
                    new GraphQLSuccess('updateMeProfile', UpdateMeProfile::class),
                    [],
                    static function (): array {
                        return [
                            'first_name'     => 'first',
                            'last_name'      => 'last',
                            'title'          => 'Mr',
                            'academic_title' => 'Professor',
                            'office_phone'   => '+1-202-555-0197',
                            'mobile_phone'   => '+1-202-555-0147',
                            'contact_email'  => 'test@gmail.com',
                            'department'     => 'HR',
                            'job_title'      => 'Manger',
                            'photo'          => UploadedFile::fake()->create('photo.jpg', 200),
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new User(['attributes' => []]));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                    },
                ],
                'user not exists'                       => [
                    new GraphQLError('updateMeProfile', new UserDoesntExists()),
                    [],
                    static function (): array {
                        return [
                            'first_name' => 'first',
                            'last_name'  => 'last',
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andThrow(new UserDoesntExists());
                        $mock
                            ->shouldReceive('updateUser')
                            ->never();
                    },
                ],
                'invalid request/Invalid contact email' => [
                    new GraphQLError('updateMeProfile', static function (): array {
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
                    new GraphQLError('updateMeProfile', static function (): array {
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
                    new GraphQLError('updateMeProfile', static function (): array {
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
                    new GraphQLSuccess('updateMeProfile', updateMeProfile::class),
                    [],
                    static function (): array {
                        return [
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
                        ];
                    },
                    static function (MockInterface $mock): void {
                        $mock
                            ->shouldReceive('getUserById')
                            ->once()
                            ->andReturn(new User(['attributes' => []]));
                        $mock
                            ->shouldReceive('updateUser')
                            ->once()
                            ->andReturn(true);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
