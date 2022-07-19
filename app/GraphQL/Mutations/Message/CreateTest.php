<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Message;

use App\Mail\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Message\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<string,mixed> $input
     * @param SettingsFactory     $settings
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settings = null,
        array $input = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setSettings($settings);

        Mail::fake();

        $input ??= [
            'subject' => 'subject',
            'message' => 'change request',
        ];

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($input: MessageInput!) {
                    message {
                        create(input: $input) {
                            result
                        }
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            Mail::assertSent(Message::class);
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        $settings = [
            'ep.email_address' => 'test@example.com',
        ];

        return (new CompositeDataProvider(
            new AuthOrgDataProvider('message'),
            new OrgUserDataProvider('message'),
            new ArrayDataProvider([
                'ok'              => [
                    new GraphQLSuccess(
                        'message',
                        new JsonFragment('create', [
                            'result' => true,
                        ]),
                    ),
                    $settings,
                    [
                        'subject' => 'subject',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                        'files'   => [
                            UploadedFile::fake()->create('documents.csv', 100),
                        ],
                    ],
                ],
                'Invalid subject' => [
                    new GraphQLValidationError('message'),
                    $settings,
                    [
                        'subject' => '',
                        'message' => 'change request',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid message' => [
                    new GraphQLValidationError('message'),
                    $settings,
                    [
                        'subject' => 'subject',
                        'message' => '',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid cc'      => [
                    new GraphQLValidationError('message'),
                    $settings,
                    [
                        'subject' => 'subject',
                        'message' => 'message',
                        'cc'      => ['wrong'],
                        'bcc'     => ['bcc@example.com'],
                    ],
                ],
                'Invalid bcc'     => [
                    new GraphQLValidationError('message'),
                    $settings,
                    [
                        'subject' => 'subject',
                        'message' => 'message',
                        'cc'      => ['cc@example.com'],
                        'bcc'     => ['wrong'],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
