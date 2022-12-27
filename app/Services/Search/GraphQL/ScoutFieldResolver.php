<?php declare(strict_types = 1);

namespace App\Services\Search\GraphQL;

use App\Services\Search\Configuration;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\GraphQL\Builder\Contracts\Scout\FieldResolver;
use LastDragon_ru\LaraASP\GraphQL\Builder\Property;

use function implode;

class ScoutFieldResolver implements FieldResolver {
    public function getField(Model $model, Property $property): string {
        return Configuration::getPropertyName(implode('.', $property->getPath()));
    }
}
