<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Note;
use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\DeleteQuoteNote
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class DeleteQuoteNoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory                              $orgFactory
     * @param UserFactory                                      $userFactory
     * @param Closure(static, ?Organization, ?User): Note|null $dataFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $dataFactory = null,
        bool $exists = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $note = null;

        if ($dataFactory) {
            $note = $dataFactory($this, $org, $user);
        }

        $this
            ->graphQL(/** @lang GraphQL */ 'mutation deleteQuoteNote($input: DeleteQuoteNoteInput!) {
                deleteQuoteNote(input:$input) {
                    deleted
                }
            }', ['input' => ['id' => $note?->getKey() ?: $this->faker->uuid()]])
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            self::assertEquals($exists, $note->exists());
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new MergeDataProvider([
            'quotes-view'    => new CompositeDataProvider(
                new AuthOrgDataProvider('deleteQuoteNote'),
                new OrgUserDataProvider('deleteQuoteNote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'          => [
                        new GraphQLSuccess('deleteQuoteNote', [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->create([
                                'user_id' => $user,
                            ]);
                        },
                        false,
                    ],
                    'not owner'   => [
                        new GraphQLUnauthorized('deleteQuoteNote'),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->for(User::factory())->create();
                        },
                        true,
                    ],
                    'system note' => [
                        new GraphQLUnauthorized('deleteQuoteNote'),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->create([
                                'user_id' => $user,
                                'note'    => null,
                            ]);
                        },
                        false,
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new AuthOrgDataProvider('deleteQuoteNote'),
                new OrgUserDataProvider('deleteQuoteNote', [
                    'org-administer',
                ]),
                new ArrayDataProvider([
                    'ok'          => [
                        new GraphQLSuccess('deleteQuoteNote', [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->create([
                                'user_id' => $user,
                            ]);
                        },
                        false,
                    ],
                    'not owner'   => [
                        new GraphQLSuccess('deleteQuoteNote', [
                            'deleted' => true,
                        ]),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->hasFiles(1)->for(User::factory())->create();
                        },
                        false,
                    ],
                    'system note' => [
                        new GraphQLUnauthorized('deleteQuoteNote'),
                        static function (TestCase $test, ?Organization $org, ?User $user): Note {
                            return Note::factory()->ownedBy($org)->create([
                                'user_id' => $user,
                                'note'    => null,
                            ]);
                        },
                        false,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
