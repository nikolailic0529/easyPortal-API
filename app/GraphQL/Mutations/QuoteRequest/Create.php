<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\QuoteRequest;

use App\Mail\QuoteRequest as QuoteRequestMail;
use App\Models\Contact;
use App\Models\File;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAsset;
use App\Models\QuoteRequestDocument;
use App\Services\Auth\Auth;
use App\Services\Filesystem\ModelDiskFactory;
use App\Services\Organization\CurrentOrganization;
use App\Utils\Cache\CacheKey;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection;

class Create {
    public function __construct(
        protected Auth $auth,
        protected CurrentOrganization $organization,
        protected ModelDiskFactory $disks,
        protected Mailer $mailer,
    ) {
        // empty
    }

    /**
     * @param array<string, array{input: array<string, mixed>}> $args
     */
    public function __invoke(mixed $root, array $args): QuoteRequest|bool {
        $input                    = new CreateInput($args['input']);
        $request                  = new QuoteRequest();
        $request->organization    = $this->organization->get();
        $request->user            = $this->auth->getUser();
        $request->oem_id          = $input->oem_id;
        $request->oem_custom      = $input->oem_custom;
        $request->type_id         = $input->type_id;
        $request->type_custom     = $input->type_custom;
        $request->message         = $input->message;
        $request->customer_id     = $input->customer_id;
        $request->customer_custom = $input->customer_custom;
        $request->contact         = $this->getContact($request, $input);
        $request->files           = $this->getFiles($request, $input);
        $request->assets          = $this->getAssets($request, $input);
        $request->documents       = $this->getDocuments($request, $input);
        $result                   = $request->save();

        if ($result) {
            $this->mailer->send(new QuoteRequestMail($request));
        }

        return $result ? $request : false;
    }

    protected function getContact(QuoteRequest $request, CreateInput $input): Contact {
        $contact               = new Contact();
        $contact->name         = $input->contact_name;
        $contact->email        = $input->contact_email;
        $contact->phone_number = $input->contact_phone;
        $contact->phone_valid  = true;

        return $contact;
    }

    /**
     * @return Collection<int, File>
     */
    protected function getFiles(QuoteRequest $request, CreateInput $input): Collection {
        return new Collection(
            $this->disks->getDisk($request)->storeToFiles($input->files ?? []),
        );
    }

    /**
     * @return Collection<int, QuoteRequestAsset>
     */
    protected function getAssets(QuoteRequest $request, CreateInput $input): Collection {
        /** @var Collection<array-key, QuoteRequestAsset> $assets */
        $assets = new Collection();

        foreach ((array) $input->assets as $asset) {
            $quoteAsset                       = new QuoteRequestAsset();
            $quoteAsset->asset_id             = $asset->asset_id;
            $quoteAsset->duration_id          = $asset->duration_id;
            $quoteAsset->service_level_id     = $asset->service_level_id;
            $quoteAsset->service_level_custom = $asset->service_level_custom;

            $key          = (string) new CacheKey([
                $quoteAsset->asset_id,
                $quoteAsset->duration_id,
                $quoteAsset->service_level_id,
                $quoteAsset->service_level_custom,
            ]);
            $assets[$key] = $quoteAsset;
        }

        return $assets->values();
    }

    /**
     * @return Collection<int, QuoteRequestDocument>
     */
    protected function getDocuments(QuoteRequest $request, CreateInput $input): Collection {
        /** @var Collection<array-key, QuoteRequestDocument> $documents */
        $documents = new Collection();

        foreach ((array) $input->documents as $document) {
            $quoteDocument              = new QuoteRequestDocument();
            $quoteDocument->document_id = $document->document_id;
            $quoteDocument->duration_id = $document->duration_id;

            $key             = (string) new CacheKey([
                $quoteDocument->document_id,
                $quoteDocument->duration_id,
            ]);
            $documents[$key] = $quoteDocument;
        }

        return $documents->values();
    }
}
