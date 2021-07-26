<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Document;
use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;

class DocumentNotes {
    /**
     * @return array<mixed>
     */
    public function __invoke(Document $document): Builder {
        return Note::query()->where('document_id', '=', $document->getKey());
    }
}
