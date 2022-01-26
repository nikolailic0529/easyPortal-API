<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Models\Enums\UserType;
use App\Models\User;
use Closure;
use GraphQL\Type\Introspection;
use Illuminate\Contracts\Config\Repository;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Symfony\Component\Finder\Finder;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function dirname;
use function preg_quote;

/**
 * @coversNothing
 */
class SchemaTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testSchema(): void {
        $this->assertGraphQLSchemaEquals($this->getGraphQLSchemaExpected());
    }

    /**
     * @dataProvider dataProviderIntrospection
     */
    public function testIntrospection(
        Response $expected,
        Closure $settingsFactory,
        Closure $userFactory,
    ): void {
        $this->setSettings($settingsFactory);
        $this->setUser($userFactory);

        $this
            ->graphQL(Introspection::getIntrospectionQuery())
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderForbiddenDirectives
     */
    public function testForbiddenDirectives(string $directive, ?string $replacement, ?string $regexp): void {
        $path = $this->app->make(Repository::class)->get('lighthouse.schema.register');
        $path = dirname($path);

        if (!$regexp) {
            $name   = preg_quote($directive, '/');
            $regexp = "/(^|\s+){$name}(^|\s+)/ui";
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

        $this->assertEquals([], $usages, $replacement);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderIntrospection(): array {
        $success  = new GraphQLSuccess('__schema', null);
        $failed   = new GraphQLError('__schema');
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
        $user     = static function (): ?User {
            return User::factory()->create();
        };
        $root     = static function (): ?User {
            return User::factory()->create([
                'type' => UserType::local(),
            ]);
        };

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
            ['@guard', '@me', null],
            ['@orderBy', '@sortBy', null],
            ['@whereConditions', '@searchBy', null],
            ['@whereHasConditions', '@searchBy', null],
            ['@spread', null, null],
            ['@hash', null, null],
            ['@globalId', null, null],
            ['@trim', null, null],
            ['@rename', null, null],
            ['@paginate', '@paginated', null],
            ['@hasMany(type: xxx)', '@hasMany @paginatedRelation', '/@hasMany\(.*?type:.*?\)/ui'],
            ['@belongsToMany(type: xxx)', '@belongsToMany @paginatedRelation', '/@belongsToMany\(.*?type:.*?\)/ui'],
            ['@morphMany(type: xxx)', '@morphMany @paginatedRelation', '/@morphMany\(.*?type:.*?\)/ui'],
            ['@paginatedLimit', null, null],
            ['@paginatedOffset', null, null],
            ['@cache', '@cached', null],
            ['@cacheKey', '@cached', null],
        ];
    }
    //</editor-fold>
}
