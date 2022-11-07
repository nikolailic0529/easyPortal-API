<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\File;
use App\Models\Note;
use App\Models\QuoteRequest;
use App\Services\Filesystem\ModelDiskFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Symfony\Component\HttpFoundation\Response;

class FilesController extends Controller {
    public function __construct(
        protected Gate $gate,
        protected ModelDiskFactory $disks,
    ) {
        // empty
    }

    public function __invoke(File $file): Response {
        // Check permissions
        $object  = $file->object;
        $allowed = false;

        if ($object instanceof Note) {
            $document = $object->document;

            if ($document) {
                if ($document->is_contract) {
                    $allowed = $this->gate->any(['contracts-view'], [$object]);
                } elseif ($document->is_quote) {
                    $allowed = $this->gate->any(['quotes-view'], [$object]);
                } else {
                    // empty
                }
            }
        } elseif ($object instanceof QuoteRequest || $object instanceof ChangeRequest) {
            // These objects automatically added into the Notes. So users who
            // can view Documents can also view it.
            $allowed = $this->gate->any(
                ['contracts-view', 'quotes-view'],
                [$object],
            );
        } else {
            // empty
        }

        if (!$allowed || !$object) {
            throw new AuthorizationException();
        }

        // Return
        return $this->disks->getDisk($object)->download($file);
    }
}
