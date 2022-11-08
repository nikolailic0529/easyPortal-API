<?php declare(strict_types = 1);

namespace App\Http\Controllers\Export\Rows;

use App\Http\Controllers\Export\Utils\Group;

class GroupEndRow extends Row {
    /**
     * @param array<Group> $groups
     */
    public function __construct(
        protected array $groups,
    ) {
        parent::__construct([]);
    }

    /**
     * @return array<Group>
     */
    public function getGroups(): array {
        return $this->groups;
    }
}
