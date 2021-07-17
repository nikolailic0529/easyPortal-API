<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\File;
use App\Rules\ContractId;
use App\Rules\QuoteId;
use App\Services\Filesystem\Disks\NotesDisk;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class FilesController extends Controller {
    public function __construct(
        protected AuthManager $auth,
        protected NotesDisk $disk,
        protected ResponseFactory $response,
        protected ContractId $contractId,
        protected QuoteId $quoteId,
    ) {
        // empty
    }
    public function __invoke(string $id): BinaryFileResponse {
        if (!$this->auth->check()) {
            throw new AuthenticationException();
        }
        $file = File::whereKey($id)->first();
        if (!$file) {
            throw new NotFoundResourceException();
        }
        $document_id = $file->note->document_id;
        $permissions = ['customers-view'];
        if ($this->contractId->passes(null, $document_id)) {
            $permissions[] = 'contracts-view';
        } elseif ($this->quoteId->passes(null, $document_id)) {
            $permissions[] = 'quotes-view';
        } else {
            // empty
        }
        if (!$this->auth->user()->canAny($permissions, [$file->note])) {
            throw new AuthorizationException();
        }

        return $this->response->download($this->disk->filesystem()->path($file->path));
    }
}
