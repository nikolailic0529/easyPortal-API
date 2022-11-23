<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\User;

use App\Utils\JsonObject\JsonObject;
use Illuminate\Http\UploadedFile;

class UpdateInput extends JsonObject {
    public bool          $enabled;
    public string        $given_name;
    public string        $family_name;
    public ?string       $title;
    public ?string       $academic_title;
    public ?string       $office_phone;
    public ?string       $mobile_phone;
    public ?string       $contact_email;
    public ?string       $job_title;
    public ?UploadedFile $photo;
    public ?string       $homepage;
    public ?string       $locale;
    public ?string       $timezone;
    public ?string       $freshchat_id;
}
