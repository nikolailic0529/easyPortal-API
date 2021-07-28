<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\RequestChange;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\RequestOrganizationChange
 */
class RequestOrganizationChangeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $input
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        Closure $prepare = null,
        array $input = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);
        $this->setSettings($settings);

        Mail::fake();

        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        $input = $input ?: [
            'from'    => 'user@example.com',
            'subject' => 'subject',
            'message' => 'message',
        ];
        $map   = [];
        $file  = [];

        if (array_key_exists('files', $input)) {
            if (!empty($input['files'])) {
                foreach ($input['files'] as $index => $item) {
                    $file[$index] = $item;
                    $map[$index]  = ["variables.input.files.{$index}"];
                }
                $input['files'] = null;
            }
        }

        $query      = /** @lang GraphQL */ 'mutation RequestOrganizationChange($input: RequestOrganizationChangeInput!)
        {
            requestOrganizationChange(input:$input) {
                created {
                    subject
                    message
                    from
                    to
                    cc
                    bcc
                    user_id
                    user {
                        id
                        given_name
                        family_name
                    }
                    files {
                        name
                    }
                }
            }
        }';
        $operations = [
            'operationName' => 'RequestOrganizationChange',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];
        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(RequestChange::class);
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
            if ($user) {
                $user->id          = 'fd421bad-069f-491c-ad5f-5841aa9a9dee';
                $user->given_name  = 'first';
                $user->family_name = 'last';
                $user->save();
            }
        };
        $settings = [
            'ep.email_address' => 'test@example.com',
        ];

        return (new CompositeDataProvider(
            new OrganizationDataProvider('requestOrganizationChange'),
            new UserDataProvider('requestOrganizationChange', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'              => [
                    new GraphQLSuccess('requestOrganizationChange', RequestAssetChange::class, [
                        'created' => [
                            'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                            'subject' => 'subject',
                            'message' => 'change request',
                            'from'    => 'user@example.com',
                            'to'      => ['test@example.com'],
                            'cc'      => ['cc@example.com'],
                            'bcc'     => ['bcc@example.com'],
                            'user'    => [
                                'id'          => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                                'given_name'  => 'first',
                                'family_name' => 'last',
                            ],
                            'files'   => [
                                [
                                    'name' => 'documents.csv',
                                ],
                            ],
                        ],
                    ]),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'from'    => 'user@example.com',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                        'files'   => [
                            UploadedFile::fake()->create('documents.csv', 100),
                        ],
                    ],
                ],
                'Invalid subject' => [
                    new GraphQLError('requestOrganizationChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject' => '',
                        'message' => 'change request',
                        'from'    => 'user@example.com',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid message' => [
                    new GraphQLError('requestOrganizationChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => '',
                        'from'    => 'user@example.com',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid cc'      => [
                    new GraphQLError('requestOrganizationChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => 'message',
                        'from'    => 'user@example.com',
                        'cc'      => ['wrong'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid bcc'     => [
                    new GraphQLError('requestOrganizationChange', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    $settings,
                    $prepare,
                    [
                        'subject' => 'subject',
                        'message' => 'message',
                        'from'    => 'user@example.com',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['wrong'],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
