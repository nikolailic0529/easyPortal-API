<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories;

use App\Models\Contact;
use App\Models\Model;
use App\Services\DataLoader\Normalizer;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Schema\CompanyContactPerson;
use App\Services\DataLoader\Schema\Type;
use InvalidArgumentException;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Psr\Log\LoggerInterface;

use function is_null;
use function sprintf;

class ContactFactory extends DependentModelFactory {
    public function __construct(
        LoggerInterface $logger,
        Normalizer $normalizer,
        protected ContactResolver $contacts,
    ) {
        parent::__construct($logger, $normalizer);
    }

    public function find(Model $object, Type $type): ?Contact {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::find($object, $type);
    }

    public function create(Model $object, Type $type): ?Contact {
        $model = null;

        if ($type instanceof CompanyContactPerson) {
            $model = $this->createFromPerson($object, $type);
        } else {
            throw new InvalidArgumentException(sprintf(
                'The `$type` must be instance of `%s`.',
                CompanyContactPerson::class,
            ));
        }

        return $model;
    }

    protected function createFromPerson(Model $object, CompanyContactPerson $person): ?Contact {
        // CompanyContactPerson can be without name and phone
        if (is_null($person->name) && is_null($person->phoneNumber)) {
            return null;
        }

        // Phone can be in a local format we should convert it into e164 if
        // this is not possible we save it as is and mark it as invalid.
        $phone = $person->phoneNumber;
        $valid = false;

        if ($phone) {
            try {
                $phone = PhoneNumber::make($phone)->formatE164();
                $valid = true;
            } catch (NumberParseException) {
                $valid = false;
            }
        } else {
            $phone = null;
            $valid = null;
        }

        // Contact
        $object = $this->contact($object, $person->name, $phone, $valid);

        // Return
        return $object;
    }

    protected function contact(Model $object, ?string $name, ?string $phone, ?bool $valid): Contact {
        $contact = $this->contacts->get(
            $object,
            $name,
            $phone,
            $this->factory(function () use ($object, $name, $phone, $valid): Contact {
                $model = new Contact();

                if (!is_null($name)) {
                    $name = $this->normalizer->string($name);
                }

                if (!is_null($phone)) {
                    $phone = $this->normalizer->string($phone);
                }

                $model->object_type  = $object->getMorphClass();
                $model->object_id    = $object->getKey();
                $model->name         = $name;
                $model->phone_number = $phone;
                $model->phone_valid  = $valid;

                $model->save();

                return $model;
            }),
        );

        return $contact;
    }
}
