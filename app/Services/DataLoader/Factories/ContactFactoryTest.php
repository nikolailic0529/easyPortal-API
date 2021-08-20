<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Model;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\Type;
use Closure;
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
        $customer = Customer::factory()->create();
        $contact  = new CompanyContactPerson([
            'phoneNumber' => '+495921234554',
            'vendor'      => 'HPE',
            'name'        => null,
            'type'        => 'SYSTEM_MANAGER',
            'mail'        => 'manger@hpe.com',
        ]);
        $factory  = $this->app->make(ContactFactory::class);

        // Exist
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
        $contact  = new CompanyContactPerson([
            'phoneNumber' => '+495921234554',
            'vendor'      => 'HPE',
            'name'        => null,
            'type'        => 'SYSTEM_MANAGER',
            'mail'        => 'manger@hpe.com',
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
                $contact->mail,
            )
            ->andReturns();

        $factory->create($customer, $contact);
    }


    /**
     * @covers ::contact
     *
     * @dataProvider dataProviderContact
     */
    public function testContact(Closure $factory): void {
        // Prepare
        $normalizer = $this->app->make(Normalizer::class);
        $resolver   = $this->app->make(ContactResolver::class);
        $customer   = $factory($this);
        $contact    = Contact::factory()->create([
            'object_type' => $customer->getMorphClass(),
            'object_id'   => $customer->getKey(),
        ]);

        $factory = new class($normalizer, $resolver) extends ContactFactory {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected Normalizer $normalizer,
                protected ContactResolver $contactResolver,
            ) {
                // empty
            }

            public function contact(
                Model $object,
                ?string $name,
                ?string $phone,
                ?bool $valid,
                ?string $mail,
            ): Contact {
                return parent::contact($object, $name, $phone, $valid, $mail);
            }
        };

        $this->flushQueryLog();

        // If model exists - no action required
        if ($customer->exists) {
            $this->assertEquals(
                $contact,
                $factory->contact($customer, $contact->name, $contact->phone_number, true, $contact->email),
            );
            $this->assertCount(1, $this->getQueryLog());
        } else {
            $this->assertNotNull(
                $factory->contact($customer, $contact->name, $contact->phone_number, true, $contact->email),
            );
            $this->assertCount(0, $this->getQueryLog());
        }

        $this->flushQueryLog();

        // If not - it should be created
        $created = $factory->contact($customer, ' new  Name ', ' phone   number ', false, ' email ');

        $this->assertNotNull($created);
        $this->assertEquals($customer->exists, $created->exists);
        $this->assertEquals($customer->getMorphClass(), $created->object_type);
        $this->assertEquals($customer->getKey(), $created->object_id);
        $this->assertEquals('new Name', $created->name);
        $this->assertEquals('phone number', $created->phone_number);
        $this->assertEquals('email', $created->email);
        $this->assertFalse($created->phone_valid);
        $this->assertCount($customer->exists ? 2 : 0, $this->getQueryLog());
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
