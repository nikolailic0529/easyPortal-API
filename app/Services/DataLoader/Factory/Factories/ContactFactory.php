<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Factories;

use App\Models\Contact;
use App\Services\DataLoader\Factory\DependentModelFactory;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Schema\Type;
use App\Services\DataLoader\Schema\Types\CompanyContactPerson;
use App\Utils\Eloquent\Model;
use Illuminate\Contracts\Debug\ExceptionHandler;
use InvalidArgumentException;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

use function is_null;
use function sprintf;

/**
 * @extends DependentModelFactory<Contact>
 */
class ContactFactory extends DependentModelFactory {
    public function __construct(
        ExceptionHandler $exceptionHandler,
        Normalizer $normalizer,
        protected ContactResolver $contactResolver,
    ) {
        parent::__construct($exceptionHandler, $normalizer);
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
        $object = $this->contact($object, $person->name, $phone, $valid, $person->mail);

        // Return
        return $object;
    }

    protected function contact(Model $object, ?string $name, ?string $phone, ?bool $valid, ?string $mail): Contact {
        $contact = $this->contactResolver->get(
            $object,
            $name,
            $phone,
            $mail,
            static function () use ($object, $name, $phone, $valid, $mail): Contact {
                $model               = new Contact();
                $model->object_type  = $object->getMorphClass();
                $model->object_id    = $object->getKey();
                $model->name         = $name;
                $model->phone_number = $phone;
                $model->email        = $mail;
                $model->phone_valid  = $valid;

                if ($object->exists) {
                    $model->save();
                }

                return $model;
            },
        );

        return $contact;
    }
}
