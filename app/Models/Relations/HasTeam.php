<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Data\Team;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin Model
 */
trait HasTeam {
    /**
     * @return BelongsTo<Team, self>
     */
    public function team(): BelongsTo {
        return $this->belongsTo(Team::class);
    }

    public function setTeamAttribute(?Team $team): void {
        $this->team()->associate($team);
    }
}
