<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\QuoteRequest;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Auth\AuthManager;

class CreateQuoteRequest {
    public function __construct(
        protected AuthManager $auth,
        protected ModelDiskFactory $disks,
        protected CurrentOrganization $organization,
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
            $assetsInput[$assetInput['asset_id']] = [
                'duration_id'      => $assetInput['duration_id'],
                'service_level_id' => $assetInput['service_level_id'],
            ];
        }

        $request->assets = $assetsInput;
        $request->save();

        return ['created' => $request ];
    }
}
