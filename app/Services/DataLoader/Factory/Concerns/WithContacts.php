<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Contact;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Schema\Types\CompanyContactPerson;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use libphonenumber\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber;

use function is_null;

trait WithContacts {
    use Polymorphic;

    abstract protected function getContactsResolver(): ContactResolver;

    /**
     * @param array<CompanyContactPerson> $persons
     *
     * @return Collection<array-key, Contact>
     */
    protected function contacts(Model $owner, array $persons): Collection {
        return $this->polymorphic(
            $owner,
            $persons,
            static function (CompanyContactPerson $person): string {
                return $person->type;
            },
            function (Model $owner, CompanyContactPerson $person): ?Contact {
                return $this->contact($owner, $person);
            },
        );
    }

    protected function contact(Model $owner, CompanyContactPerson $person): ?Contact {
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
        $name    = $person->name;
        $mail    = $person->mail;
        $contact = $this->getContactsResolver()->get(
            $owner,
            $name,
            $phone,
            $mail,
            static function (?Contact $model) use ($owner, $name, $phone, $valid, $mail): Contact {
                if ($model) {
                    return $model;
                }

                $model               = new Contact();
                $model->object_type  = $owner->getMorphClass();
                $model->object_id    = $owner->getKey();
                $model->name         = $name;
                $model->phone_number = $phone;
                $model->email        = $mail;
                $model->phone_valid  = $valid;

                if ($owner->exists) {
                    $model->save();
                }

                return $model;
            },
        );

        return $contact;
    }
}
