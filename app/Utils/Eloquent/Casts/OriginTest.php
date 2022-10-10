<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Casts;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Utils\Eloquent\Casts\Origin
 */
class OriginTest extends TestCase {
    /**
     * @covers ::set
     */
    public function testSet(): void {
        $cast  = new Origin();
        $value = $this->faker->randomNumber();
        $model = Mockery::mock(Model::class);
        $model
            ->shouldReceive('setAttribute')
            ->with('attr', $value)
            ->once()
            ->andReturns();

        self::assertEquals($value, $cast->set($model, 'attr_origin', $value, []));
    }

    /**
     * @covers ::set
     */
    public function testSetWithoutSuffix(): void {
        self::expectException(LogicException::class);

        $cast  = new Origin();
        $value = $this->faker->randomNumber();
        $model = Mockery::mock(Model::class);

        $cast->set($model, 'attr', $value, []);
    }
}
