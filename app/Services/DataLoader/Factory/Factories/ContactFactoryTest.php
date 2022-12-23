<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Contact;
use App\Models\Customer;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\Type;
use App\Utils\Eloquent\Model;
use Closure;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Factory\Factories\ContactFactory
 */
class ContactFactoryTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc='Tests'>
    // =========================================================================
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
            self::expectException(InvalidArgumentException::class);
            self::expectErrorMessageMatches('/^The `\$type` must be instance of/');
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

        // If model exists - no action required
        $queries = $this->getQueryLog()->flush();

        if ($customer->exists) {
            self::assertEquals(
                $contact,
                $factory->contact($customer, $contact->name, $contact->phone_number, true, $contact->email),
            );
            self::assertCount(1, $queries);
        } else {
            self::assertNotNull(
                $factory->contact($customer, $contact->name, $contact->phone_number, true, $contact->email),
            );
            self::assertCount(0, $queries);
        }

        // If not - it should be created
        $queries = $this->getQueryLog()->flush();
        $created = $factory->contact($customer, ' new  Name ', ' phone   number ', false, ' email ');

        self::assertNotNull($created);
        self::assertEquals($customer->exists, $created->exists);
        self::assertEquals($customer->getMorphClass(), $created->object_type);
        self::assertEquals($customer->getKey(), $created->object_id);
        self::assertEquals('new Name', $created->name);
        self::assertEquals('phone number', $created->phone_number);
        self::assertEquals('email', $created->email);
        self::assertFalse($created->phone_valid);
        self::assertCount($customer->exists ? 2 : 0, $queries);
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
