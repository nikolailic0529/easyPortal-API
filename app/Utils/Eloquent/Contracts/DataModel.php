<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Contracts;

/**
 * Marks Model as the Data Model.
 *
 * Data Models contain data and thus cannot be deleted. The main reason it was
 * added - reduce number of preparation to test `delete()` methods (otherwise
 * models like `Status`, `Types`, etc will require creation a lot of related
 * objects such as assets, contracts, quotes, resellers, etc). Also, there are
 * no reasons/requests to delete these models.
 */
interface DataModel {
    // empty
}
