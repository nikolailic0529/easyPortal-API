<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Contact;
use App\Models\Customer;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\CompanyContactPerson;
use App\Utils\Eloquent\Model;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Tests\TestCase;
use Tests\WithQueryLogs;

use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithContacts
 */
class WithContactsTest extends TestCase {
    use WithQueryLogs;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::contacts
     */
    public function testContacts(): void {
        // Prepare
        $owner    = Customer::factory()->make();
        $existing = Contact::factory(2)->make([
            'object_type' => $owner->getMorphClass(),
            'object_id'   => $owner->getKey(),
        ]);

        $owner->setRelation('contacts', $existing);

        $factory = new class(
            $this->app->make(ExceptionHandler::class),
            $this->app->make(TypeResolver::class),
            $this->app->make(ContactResolver::class),
        ) extends Factory {
            use WithContacts {
                contacts as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected TypeResolver $typeResolver,
                protected ContactResolver $contacts,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            protected function getContactsResolver(): ContactResolver {
                return $this->contacts;
            }

            protected function getTypeResolver(): TypeResolver {
                return $this->typeResolver;
            }
        };

        // Empty call should return empty array
        self::assertTrue($factory->contacts($owner, [])->isEmpty());

        // Repeated objects should be missed
        $ca = tap(new CompanyContactPerson(), function (CompanyContactPerson $person): void {
            $person->name        = $this->faker->name();
            $person->type        = $this->faker->text(64);
            $person->phoneNumber = $this->faker->e164PhoneNumber();
            $person->mail        = $this->faker->email();
        });

        self::assertCount(1, $factory->contacts($owner, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new CompanyContactPerson(), function (CompanyContactPerson $person) use ($ca): void {
            $person->name        = $ca->name;
            $person->type        = $this->faker->word();
            $person->phoneNumber = $ca->phoneNumber;
            $person->mail        = $ca->mail;
        });
        $actual = $factory->contacts($owner, [$ca, $cb]);
        $first  = $actual->first();

        self::assertNotNull($first);
        self::assertCount(1, $actual);
        self::assertCount(2, $first->types);
        self::assertEquals($cb->phoneNumber, $first->phone_number);
        self::assertEquals($cb->mail, $first->email);
        self::assertEquals($cb->name, $first->name);
    }

    /**
     * @covers ::contact
     *
     * @dataProvider dataProviderContact
     *
     * @param Closure(static): Model $factory
     */
    public function testContact(Closure $factory): void {
        // Prepare
        $contactResolver = $this->app->make(ContactResolver::class);
        $typeResolver    = $this->app->make(TypeResolver::class);
        $owner           = $factory($this);
        $contact         = Contact::factory()->create([
            'object_type' => $owner->getMorphClass(),
            'object_id'   => $owner->getKey(),
        ]);
        $person          = new CompanyContactPerson([
            'name'        => $contact->name,
            'mail'        => $contact->email,
            'phoneNumber' => $contact->phone_number,
        ]);

        $factory = new class($contactResolver, $typeResolver) extends Factory {
            use WithContacts {
                contact as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ContactResolver $contactResolver,
                protected TypeResolver $typeResolver,
            ) {
                // empty
            }

            public function getModel(): string {
                return Model::class;
            }

            public function create(Type $type, bool $force = false): ?Model {
                return null;
            }

            protected function getContactsResolver(): ContactResolver {
                return $this->contactResolver;
            }

            protected function getTypeResolver(): TypeResolver {
                return $this->typeResolver;
            }
        };

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        if ($owner->exists) {
            self::assertEquals($contact, $factory->contact($owner, $person));
            self::assertCount(1, $queries);
        } else {
            self::assertNotNull($factory->contact($owner, $person));
            self::assertCount(0, $queries);
        }

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $person  = new CompanyContactPerson([
            'name'        => 'new Name',
            'mail'        => 'email',
            'phoneNumber' => 'phone number',
        ]);
        $created = $factory->contact($owner, $person);

        self::assertNotNull($created);
        self::assertEquals($owner->exists, $created->exists);
        self::assertEquals($owner->getMorphClass(), $created->object_type);
        self::assertEquals($owner->getKey(), $created->object_id);
        self::assertEquals('new Name', $created->name);
        self::assertEquals('phone number', $created->phone_number);
        self::assertEquals('email', $created->email);
        self::assertFalse($created->phone_valid);
        self::assertCount($owner->exists ? 2 : 0, $queries);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderContact(): array {
        return [
            'Exists'     => [
                static function (): Customer {
                    return Customer::factory()->create();
                },
            ],
            'Not Exists' => [
                static function (): Customer {
                    return Customer::factory()->make();
                },
            ],
        ];
    }
    // </editor-fold>
}
