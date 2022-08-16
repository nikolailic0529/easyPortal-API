<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\DocumentEntry;
use App\Models\DocumentEntryField;

class Field {
    public function __construct() {
        // empty
    }

    /**
     * @param array{field_id: string} $args
     */
    public function __invoke(DocumentEntry $entry, array $args): ?DocumentEntryField {
        return $entry->fields->first(static function (DocumentEntryField $field) use ($args): bool {
            return $field->field_id === $args['field_id'];
        });
    }
}
