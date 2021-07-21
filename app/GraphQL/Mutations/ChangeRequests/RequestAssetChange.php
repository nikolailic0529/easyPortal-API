<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\ChangeRequests;

use App\Mail\RequestAssetChange as MailRequestAssetChange;
use App\Models\AssetChangeRequest;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;

use function array_key_exists;

class RequestAssetChange {
    public function __construct(
        protected AuthManager $auth,
        protected CurrentOrganization $organization,
        protected Mailer $mail,
        protected Repository $config,
    ) {
        // empty
    }
    /**
     * @param  null  $_
     * @param  array<string, mixed>  $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $request                  = new AssetChangeRequest();
        $request->user_id         = $this->auth->user()->getKey();
        $request->organization_id = $this->organization->get()->getKey();
        $request->asset_id        = $args['input']['asset_id'];
        $request->message         = $args['input']['message'];
        $request->subject         = $args['input']['subject'];
        $request->from            = $args['input']['from'];
        if (array_key_exists('cc', $args['input'])) {
            $request->cc = $args['input']['cc'];
        }
        if (array_key_exists('bcc', $args['input'])) {
            $request->bcc = $args['input']['bcc'];
        }
        $request->save();
        // Send Email

        $this->mail->to($this->config->get('ep.email_address'))
            ->send(new MailRequestAssetChange($request));
        return ['created' => $request];
    }
}
