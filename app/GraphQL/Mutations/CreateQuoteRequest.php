<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\QuoteRequest as MailQuoteRequest;
use App\Models\Contact;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAsset;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Mail\Mailer;

class CreateQuoteRequest {
    public function __construct(
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
        protected ModelDiskFactory $disks,
        protected Mailer $mail,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $request                  = new QuoteRequest();
        $request->oem_id          = $args['input']['oem_id'];
        $request->organization_id = $this->organization->get()->getKey();
        $request->user_id         = $this->auth->user()->getKey();
        $request->customer_id     = $args['input']['customer_id'] ?? null;
        $request->customer_name   = $args['input']['customer_name'] ?? null;
        $request->type_id         = $args['input']['type_id'];
        $request->message         = $args['input']['message'] ?? null;
        // request save
        $request->save();

        // Contact
        $contact               = new Contact();
        $contact->name         = $args['input']['contact_name'];
        $contact->email        = $args['input']['contact_email'];
        $contact->phone_number = $args['input']['contact_phone'];
        $contact->phone_valid  = true;
        $contact->object_id    = $request->getKey();
        $contact->object_type  = $request->getMorphClass();
        $request->contact      = $contact;


        // Files
        $request->files = $this->disks->getDisk($request)->storeToFiles($args['input']['files'] ?? []);

        // Assets
        $assetsInput = [];
        if ($args['input']['assets']) {
            foreach ($args['input']['assets'] as $assetInput) {
                $quoteRequestAsset                   = new QuoteRequestAsset();
                $quoteRequestAsset->asset_id         = $assetInput['asset_id'];
                $quoteRequestAsset->duration_id      = $assetInput['duration_id'];
                $quoteRequestAsset->service_level_id = $assetInput['service_level_id'];
                $assetsInput[]                       = $quoteRequestAsset;
            }
        }

        $request->assets = $assetsInput;
        $request->save();

        // Send Email
        $this->mail->send(new MailQuoteRequest($request));
        return ['created' => $request ];
    }
}
