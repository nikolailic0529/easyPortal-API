<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Message;

use App\GraphQL\Objects\MessageInput;
use App\Mail\Message;
use App\Models\Document;
use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
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

use function trans;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Message\Create
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CreateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
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

    public function testCreateRequestFromDocument(): void {
        // Prepare
        $org      = $this->setOrganization(Organization::factory()->create());
        $user     = $this->setUser(User::factory()->create());
        $input    = new MessageInput([
            'subject' => $this->faker->sentence(),
            'message' => $this->faker->text(),
        ]);
        $document = Document::factory()->ownedBy($org)->create();
        $mutation = $this->app->make(Create::class);

        // Request
        $request = $mutation->createRequest($document, $input);

        self::assertEquals($org->getKey(), $request->organization_id);
        self::assertEquals($user->getKey(), $request->user_id);
        self::assertEquals($input->subject, $request->subject);
        self::assertEquals($input->message, $request->message);

        // Note
        $note = Note::query()
            ->where('change_request_id', '=', $request->getKey())
            ->first();

        self::assertNotNull($note);
        self::assertNull($note->note);
        self::assertFalse($note->pinned);
        self::assertEquals($org->getKey(), $note->organization_id);
        self::assertEquals($user->getKey(), $note->user_id);
        self::assertEquals($request->getKey(), $note->change_request_id);
        self::assertEquals($document->getKey(), $note->document_id);
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
                'ok'            => [
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
                'Invalid input' => [
                    new GraphQLValidationError('message', static function (): array {
                        return [
                            'input.subject' => [
                                trans('validation.required'),
                            ],
                            'input.message' => [
                                trans('validation.required'),
                            ],
                            'input.cc.0'    => [
                                trans('validation.email'),
                            ],
                            'input.bcc.0'   => [
                                trans('validation.email'),
                            ],
                        ];
                    }),
                    $settings,
                    [
                        'subject' => '',
                        'message' => '',
                        'cc'      => ['wrong'],
                        'bcc'     => ['wrong'],
                    ],
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
