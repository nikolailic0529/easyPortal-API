<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Model;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factories\ContactFactory
 */
class ContactFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc='Tests'>
    // =========================================================================
    /**
     * @covers ::find
     */
    public function testFind(): void {
        $customer = Customer::factory()->make();
        $contact  = CompanyContactPerson::create([
            'phoneNumber' => '+495921234554',
            'vendor'      => 'HPE',
            'name'        => null,
            'type'        => 'SYSTEM_MANAGER',
        ]);
        $factory  = $this->app->make(ContactFactory::class);

        $this->flushQueryLog();

        $factory->find($customer, $contact);

        $this->assertCount(1, $this->getQueryLog());
    }

    /**
     * @covers ::create
     *
     * @dataProvider dataProviderCreate
     */
    public function testCreate(?string $expected, Type $type): void {
        $customer = new Customer();
        $factory  = Mockery::mock(ContactFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        if ($expected) {
            $factory->shouldReceive($expected)
                ->once()
                ->with($customer, $type)
                ->andReturns();
        } else {
            $this->expectException(InvalidArgumentException::class);
            $this->expectErrorMessageMatches('/^The `\$type` must be instance of/');
        }

        $factory->create($customer, $type);
    }

    /**
     * @covers ::createFromPerson
     */
    public function testCreateFromPerson(): void {
        $customer = Customer::factory()->make();
        $contact  = CompanyContactPerson::create([
            'phoneNumber' => '+495921234554',
            'vendor'      => 'HPE',
            'name'        => null,
            'type'        => 'SYSTEM_MANAGER',
        ]);

        $factory = Mockery::mock(ContactFactory::class);
        $factory->makePartial();
        $factory->shouldAllowMockingProtectedMethods();

        $factory
            ->shouldReceive('contact')
            ->once()
            ->with(
                $customer,
                $contact->name,
                '+495921234554',
                Mockery::any(),
            )
            ->andReturns();

        $factory->create($customer, $contact);
    }


    /**
     * @covers ::contact
     */
    public function testContact(): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ContactResolver::class);
        $customer   = Customer::factory()->make();
        $contact    = Contact::factory()->create([
            'object_type' => $customer->getMorphClass(),
            'object_id'   => $customer->getKey(),
        ]);

        $factory = new class($normalizer, $resolver) extends ContactFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(Normalizer $normalizer, ContactResolver $resolver) {
                $this->normalizer = $normalizer;
                $this->contacts   = $resolver;
            }

            public function contact(Model $object, ?string $name, ?string $phone, ?bool $valid): Contact {
                return parent::contact($object, $name, $phone, $valid);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        $this->assertEquals($contact, $factory->contact($customer, $contact->name, $contact->phone_number, true));
        $this->assertCount(1, $this->getQueryLog());

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->contact($customer, ' new  Name ', ' phone   number ', false);

        $this->assertNotNull($created);
        $this->assertTrue($created->wasRecentlyCreated);
        $this->assertEquals($customer->getMorphClass(), $created->object_type);
        $this->assertEquals($customer->getKey(), $created->object_id);
        $this->assertEquals('new Name', $created->name);
        $this->assertEquals('phone number', $created->phone_number);
        $this->assertFalse($created->phone_valid);
        $this->assertNull($created->email);
        $this->assertCount(2, $this->getQueryLog());
    }
    // </editor-fold>

    // <editor-fold desc='DataProviders'>
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderCreate(): array {
        return [
            CompanyContactPerson::class => ['createFromPerson', new CompanyContactPerson()],
            'Unknown'                   => [
                null,
                new class() extends Type {
                    // empty
                },
            ],
        ];
    }
    // </editor-fold>
}
