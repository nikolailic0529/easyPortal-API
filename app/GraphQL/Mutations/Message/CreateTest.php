<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Message;

use App\Mail\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Message\Create
 *
 * @phpstan-import-type OrganizationFactory from \Tests\WithOrganization
 * @phpstan-import-type UserFactory from \Tests\WithUser
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
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        array $settings = null,
        array $input = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));
        $this->setSettings($settings);

        Mail::fake();

        $map     = [];
        $file    = [];
        $input ??= [
            'subject' => 'subject',
            'message' => 'change request',
        ];

        if (isset($input['files'])) {
            foreach ((array) $input['files'] as $index => $item) {
                $file[$index] = $item;
                $map[$index]  = ["variables.input.files.{$index}"];
            }

            $input['files'] = null;
        }

        $query      = /** @lang GraphQL */
            <<<'GRAPHQL'
            mutation test($input: MessageCreateInput!) {
                message {
                    create(input: $input) {
                        result
                    }
                }
            }
        GRAPHQL;
        $operations = [
            'operationName' => 'test',
            'query'         => $query,
            'variables'     => [
                'input' => $input,
            ],
        ];

        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

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
            new OrganizationDataProvider('message'),
            new OrganizationUserDataProvider('message'),
            new ArrayDataProvider([
                'ok'              => [
                    new GraphQLSuccess(
                        'message',
                        null,
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
