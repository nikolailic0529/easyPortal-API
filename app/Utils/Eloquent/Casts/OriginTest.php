<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Casts;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Utils\Eloquent\Casts\Origin
 */
class OriginTest extends TestCase {
    public function testSet(): void {
        $value = $this->faker->randomNumber();
        $model = new class() extends Model {
            /**
             * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
             */
            protected $casts = [
                'attr_origin' => Origin::class,
            ];
        };

        $model->setAttribute('attr_origin', $value);

        self::assertEquals($value, $model->getAttribute('attr_origin'));
        self::assertEquals($value, $model->getAttribute('attr'));
    }

    public function testSetWithoutSuffix(): void {
        self::expectException(LogicException::class);

        $cast  = new Origin();
        $value = $this->faker->randomNumber();
        $model = Mockery::mock(Model::class);

        $cast->set($model, 'attr', $value, []);
    }
}
