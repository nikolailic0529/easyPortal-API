<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\RequestChange;
use App\Models\Asset;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Document;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Mail\Mailer;

use function array_filter;
use function array_unique;

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
        $request = $this->createRequest(
            $args['input']['asset_id'],
            (new Asset())->getMorphClass(),
            $args['input']['subject'],
            $args['input']['message'],
            $args['input']['from'],
            $args['input']['cc'] ?? null,
            $args['input']['bcc'] ?? null,
            new Asset(),
        );
        return ['created' => $request];
    }

    /**
     * @param array<string>|null $cc
     *
     * @param array<string>|null $bcc
     */
    public function createRequest(
        string $object_id,
        string $object_type,
        string $subject,
        string $message,
        string $from,
        array $cc = null,
        array $bcc = null,
        Asset|Document|Customer $model,
    ): ChangeRequest {
        $request                  = new ChangeRequest();
        $request->user_id         = $this->auth->user()->getKey();
        $request->organization_id = $this->organization->get()->getKey();
        $request->object_id       = $object_id;
        $request->object_type     = $object_type;
        $request->subject         = $subject;
        $request->message         = $message;
        $request->from            = $from;
        $request->to              = [$this->config->get('ep.email_address')];
        $request->cc              = array_unique(array_filter($cc)) ?: null;
        $request->bcc             = array_unique(array_filter($bcc)) ?: null;
        $request->save();

        // Send Email
        $this->mail->send(new RequestChange($request, $model));
        return $request;
    }
}
