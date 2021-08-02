<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\QuoteRequest as MailQuoteRequest;
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
        $request->customer_id     = $args['input']['customer_id'];
        $request->contact_id      = $args['input']['contact_id'];
        $request->type_id         = $args['input']['type_id'];
        $request->message         = $args['input']['message'] ?? null;
        $request->save();

        // Add files
        $request->files = $this->disks->getDisk($request)->storeToFiles($args['input']['files'] ?? []);
        $request->save();

        // Assets
        $assetsInput = [];
        foreach ($args['input']['assets'] as $assetInput) {
            $quoteRequestAsset                   = new QuoteRequestAsset();
            $quoteRequestAsset->asset_id         = $assetInput['asset_id'];
            $quoteRequestAsset->duration_id      = $assetInput['duration_id'];
            $quoteRequestAsset->service_level_id = $assetInput['service_level_id'];
            $assetsInput[]                       = $quoteRequestAsset;
        }

        $request->assets = $assetsInput;
        $request->save();

        // Send Email
        $this->mail->send(new MailQuoteRequest($request));
        return ['created' => $request ];
    }
}
