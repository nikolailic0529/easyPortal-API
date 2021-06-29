<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
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
        Closure $dataFactory = null,
        array $settings = [],
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


        $http = Http::fake([
            '*' => Http::response([], 204),
        ]);

        $this->app->instance(Factory::class, $http);

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
                ],
                'invalid request/Invalid contact email' => [
                    new GraphQLError('updateMeProfile', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
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
                    static function (TestCase $test): array {
                        return [
                            'photo' => UploadedFile::fake()->create('photo.jpg', 200),
                        ];
                    },
                    [
                        'ep.image.max_size' => 100,
                        'ep.image.formats'  => ['jpg'],
                    ],
                ],
                'invalid request/Invalid photo format'  => [
                    new GraphQLError('updateMeProfile', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'photo' => UploadedFile::fake()->create('photo.png', 100),
                        ];
                    },
                    [
                        'ep.image.max_size' => 200,
                        'ep.image.formats'  => ['jpg'],
                    ],
                ],
                'nullable data'                         => [
                    new GraphQLSuccess('updateMeProfile', updateMeProfile::class),
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
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}