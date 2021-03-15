<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Model;
use App\Services\DataLoader\Factories\ContactFactory;
use App\Services\DataLoader\Factories\ModelFactory;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\TypeResolver;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\Type;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

use function reset;
use function tap;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\Concerns\WithContacts
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
            $this->app->make(LoggerInterface::class),
            $this->app->make(Normalizer::class),
            $this->app->make(ContactFactory::class),
            $this->app->make(TypeResolver::class),
        ) extends ModelFactory {
            use WithContacts {
                objectContacts as public;
            }

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                LoggerInterface $logger,
                Normalizer $normalizer,
                ContactFactory $contacts,
                TypeResolver $types,
            ) {
                $this->logger     = $logger;
                $this->normalizer = $normalizer;
                $this->contacts   = $contacts;
                $this->types      = $types;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        // Empty call should return empty array
        $this->assertEquals([], $factory->objectContacts($owner, []));

        // Repeated objects should be missed
        $ca = tap(new CompanyContactPerson(), function (CompanyContactPerson $person): void {
            $person->name        = $this->faker->name;
            $person->type        = $this->faker->word;
            $person->phoneNumber = $this->faker->e164PhoneNumber;
        });

        $this->assertCount(1, $factory->objectContacts($owner, [$ca, $ca]));

        // Objects should be grouped by type
        $cb     = tap(new CompanyContactPerson(), function (CompanyContactPerson $person) use ($ca): void {
            $person->name        = $ca->name;
            $person->type        = $this->faker->word;
            $person->phoneNumber = $ca->phoneNumber;
        });
        $actual = $factory->objectContacts($owner, [$ca, $cb]);
        $first  = reset($actual);

        $this->assertCount(1, $actual);
        $this->assertCount(2, $first->types);
        $this->assertEquals($cb->phoneNumber, $first->phone_number);
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
            public function __construct(ContactFactory $contacts) {
                $this->contacts = $contacts;
            }

            public function create(Type $type): ?Model {
                return null;
            }
        };

        $factory->contact($owner, $contact);
    }
}
