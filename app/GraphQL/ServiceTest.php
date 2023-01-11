<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\User;
use GraphQL\Type\Introspection;
use GraphQL\Utils\BuildClientSchema;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Forbidden;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\Ok;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Symfony\Component\Finder\Finder;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\Providers\Users\RootUserProvider;
use Tests\Providers\Users\UserProvider;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_map;
use function config;
use function dirname;
use function preg_quote;

/**
 * @internal
 * @covers \App\GraphQL\Service
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class ServiceTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderIntrospection
     *
     * @param SettingsFactory $settingsFactory
     * @param UserFactory     $userFactory
     */
    public function testIntrospection(
        Response $expected,
        mixed $settingsFactory,
        mixed $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->graphQL(Introspection::getIntrospectionQuery())
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderPlayground
     *
     * @param SettingsFactory $settingsFactory
     * @param UserFactory     $userFactory
     */
    public function testPlayground(
        Response $expected,
        mixed $settingsFactory,
        mixed $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->get('/graphql-playground')
            ->assertThat($expected);
    }

    public function testSchemaPublic(): void {
        $actual   = BuildClientSchema::build(
            Introspection::fromSchema(
                $this->getDefaultGraphQLSchema(),
            ),
        );
        $expected = $this->getGraphQLSchemaExpected('~public.graphql', $actual);

        self::assertGraphQLSchemaEquals(
            $expected,
            $actual,
        );
    }

    public function testSchemaInternal(): void {
        self::assertDefaultGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected('~internal.graphql'),
        );
    }

    /**
     * @dataProvider dataProviderForbiddenDirectives
     */
    public function testForbiddenDirectives(string $directive, ?string $replacement, ?string $regexp): void {
        $path = (string) config('lighthouse.schema.register');
        $path = dirname($path);

        if (!$regexp) {
            $name   = preg_quote($directive, '/');
            $regexp = "/(^|\s+){$name}($|\s+)/ui";
        }

        $finder = Finder::create()->in($path)->files()->contains($regexp)->sortByName();
        $usages = [];

        foreach ($finder as $file) {
            $usages[$file->getPathname()] = true;
        }

        if ($replacement) {
            $replacement = "Directive {$directive} is forbidden, {$replacement} should be used instead.";
        } else {
            $replacement = "Directive {$directive} is forbidden.";
        }

        self::assertEquals([], $usages, $replacement);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderIntrospection(): array {
        $data = $this->dataProviderPlayground();
        $data = array_map(
            static function (array $case): array {
                $case[0] = $case[0] instanceof Ok
                    ? new GraphQLSuccess('__schema')
                    : new GraphQLError('__schema');

                return $case;
            },
            $data,
        );

        return $data;
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderPlayground(): array {
        $success  = new Ok();
        $failed   = new Forbidden();
        $enabled  = static function (): array {
            return [
                'app.debug' => true,
            ];
        };
        $disabled = static function (): array {
            return [
                'app.debug' => false,
            ];
        };
        $guest    = static function (): ?User {
            return null;
        };
        $user     = new UserProvider();
        $root     = new RootUserProvider();

        return (new MergeDataProvider([
            'debug on'  => new ArrayDataProvider([
                'guest' => [
                    $success,
                    $enabled,
                    $guest,
                ],
                'user'  => [
                    $success,
                    $enabled,
                    $user,
                ],
                'root'  => [
                    $success,
                    $enabled,
                    $root,
                ],
            ]),
            'debug off' => new ArrayDataProvider([
                'guest' => [
                    $failed,
                    $disabled,
                    $guest,
                ],
                'user'  => [
                    $failed,
                    $disabled,
                    $user,
                ],
                'root'  => [
                    $success,
                    $disabled,
                    $root,
                ],
            ]),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderForbiddenDirectives(): array {
        return [
            ['@guard', '@authMe', null],
            ['@orderBy', '@sortBy', null],
            ['@whereConditions', '@searchBy', null],
            ['@whereHasConditions', '@searchBy', null],
            ['@spread', null, null],
            ['@hash', null, null],
            ['@globalId', null, null],
            ['@trim', null, null],
            ['@rename', null, null],
            ['@paginate', '@paginated', null],
            ['@hasMany(type: xxx)', '@relation @paginatedRelation', '/@hasMany\(.*?type:.*?\)/ui'],
            ['@belongsToMany(type: xxx)', '@relation @paginatedRelation', '/@belongsToMany\(.*?type:.*?\)/ui'],
            ['@morphMany(type: xxx)', '@relation @paginatedRelation', '/@morphMany\(.*?type:.*?\)/ui'],
            ['@belongsTo', '@relation', null],
            ['@belongsToMany', '@relation', null],
            ['@hasOne', '@relation', null],
            ['@hasMany', '@relation', null],
            ['@hasManyThrough', '@relation', null],
            ['@hasManyThrough', '@relation @paginatedRelation', '/@hasManyThrough\(.*?type:.*?\)/ui'],
            ['@morphMany', '@relation', null],
            ['@morphOne', '@relation', null],
            ['@morphTo', '@relation', null],
            ['@morphToMany', '@relation', null],
            ['@paginatedLimit', null, null],
            ['@paginatedOffset', null, null],
            ['@cache', '@cached', null],
            ['@cacheKey', '@cached', null],
        ];
    }
    // </editor-fold>
}
