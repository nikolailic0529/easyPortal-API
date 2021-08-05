<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Models\ChangeRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Audit\Auditor
 */
class AuditorTest extends TestCase {
    /**
     * @covers ::create
     *
     */
    public function testCreate(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->make();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with($changeRequest, Action::created(), null, $changeRequest->getAttributes());
        });

        $changeRequest->save();
    }

    /**
     * @covers ::create
     *
     */
    public function testUpdated(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->create([
            'subject' => 'old',
        ]);

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with($changeRequest, Action::updated(), ['subject' => 'old'], ['subject' => 'new']);
        });

        $changeRequest->subject = 'new';
        $changeRequest->save();
    }

    /**
     * @covers ::create
     *
     */
    public function testDeleted(): void {
        $this->setUser(User::factory()->make(), $this->setOrganization(Organization::factory()->make()));
        $changeRequest = ChangeRequest::factory()->create();

        $this->override(Auditor::class, static function (MockInterface $mock) use ($changeRequest): void {
            $mock
                ->shouldReceive('create')
                ->once()
                ->with($changeRequest, Action::deleted());
        });

        $changeRequest->delete();
    }
    // </editor-fold>
}
