<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Contact;
use App\Models\Customer;
use App\Services\DataLoader\Factory\Factories\ContactFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use Tests\TestCase;

use function reset;
use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Concerns\WithContacts
 */
class WithContactsTest extends TestCase {
    /**
     * @covers ::objectContacts
     */
    public function testCustomerContacts(): void {
        // Prepare
        $owner    = Customer::factory()->make();
        $existing = Contact::factory(2)->make([
            'object_type' => $owner->getMorphClass(),
            'object_id'   => $owner->getKey(),
        ]);

        $owner->setRelation('contacts', $existing);

        $factory = new class(
            $this->app->make(ExceptionHandler::class),
            $this->app->make(Normalizer::class),
            $this->app->make(TypeResolver::class),
            $this->app->make(ContactFactory::class),
        ) extends ModelFactory {
            use WithContacts {
                objectContacts as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ExceptionHandler $exceptionHandler,
                protected Normalizer $normalizer,
                protected TypeResolver $typeResolver,
                protected ContactFactory $contacts,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getContactsFactory(): ContactFactory {
                return $this->contacts;
            }

            protected function getTypeResolver(): TypeResolver {
                return $this->typeResolver;
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->objectContacts($owner, []));

        // Repeated objects should be missed
        $ca = tap(new CompanyContactPerson(), function (CompanyContactPerson $person): void {
            $person->name        = $this->faker->name;
            $person->type        = $this->faker->text(64);
            $person->phoneNumber = $this->faker->e164PhoneNumber;
            $person->mail        = $this->faker->email;
        });

        $this->assertCount(1, $factory->objectContacts($owner, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new CompanyContactPerson(), function (CompanyContactPerson $person) use ($ca): void {
            $person->name        = $ca->name;
            $person->type        = $this->faker->word;
            $person->phoneNumber = $ca->phoneNumber;
            $person->mail        = $ca->mail;
        });
        $actual = $factory->objectContacts($owner, [$ca, $cb]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(2, $first->types);
        $this->assertEquals($cb->phoneNumber, $first->phone_number);
        $this->assertEquals($cb->mail, $first->email);
        $this->assertEquals($cb->name, $first->name);
    }

    /**
     * @covers ::contact
     */
    public function testContact(): void {
        // Prepare
        $owner   = new Customer();
        $contact = new CompanyContactPerson();
        $factory = Mockery::mock(ContactFactory::class);
        $factory
            ->shouldReceive('create')
            ->with($owner, $contact)
            ->once()
            ->andReturns();

        $factory = new class($factory) extends ModelFactory {
            use WithContacts {
                contact as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ContactFactory $contacts,
            ) {
                // empty
            }

            public function create(Type $type): ?Model {
                return null;
            }

            protected function getContactsFactory(): ContactFactory {
                return $this->contacts;
            }

            protected function getTypeResolver(): TypeResolver {
                throw new Exception('Should not be called.');
            }
        };

        $factory->contact($owner, $contact);
    }
}
