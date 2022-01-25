<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Mail\RequestChange;
use App\Models\Asset;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Organization;
use App\Services\Filesystem\ModelDiskFactory;
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
        protected ModelDiskFactory $disks,
    ) {
        // empty
    }

    /**
     * @param null                 $_
     * @param array<string, mixed> $args
     *
     * @return  array<string, mixed>
     */
    public function __invoke($_, array $args): array {
        $asset   = Asset::whereKey($args['input']['asset_id'])->first();
        $request = $this->createRequest(
            $asset,
            $args['input']['subject'],
            $args['input']['message'],
            $args['input']['from'],
            $args['input']['files'] ?? [],
            $args['input']['cc'] ?? null,
            $args['input']['bcc'] ?? null,
        );

        return ['created' => $request];
    }

    /**
     * @param array<string> $files
     *
     * @param array<string>|null $cc
     *
     * @param array<string>|null $bcc
     */
    public function createRequest(
        Asset|Document|Customer|Organization $model,
        string $subject,
        string $message,
        string $from,
        array $files = [],
        array $cc = null,
        array $bcc = null,
    ): ChangeRequest {
        $request               = new ChangeRequest();
        $request->user         = $this->auth->user();
        $request->organization = $this->organization->get();
        $request->object_id    = $model->getKey();
        $request->object_type  = $model->getMorphClass();
        $request->subject      = $subject;
        $request->message      = $message;
        $request->from         = $from;
        $request->to           = [$this->config->get('ep.email_address')];
        $request->cc           = array_unique(array_filter((array) $cc)) ?: null;
        $request->bcc          = array_unique(array_filter((array) $bcc)) ?: null;
        $request->save();

        // Add Files
        $request->files = $this->disks->getDisk($request)->storeToFiles($files);
        $request->save();

        // Send Email
        $this->mail->send(new RequestChange($request, $model));

        return $request;
    }
}
