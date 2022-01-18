<?php declare(strict_types = 1);

namespace App\Models\Relations;

use App\Models\Team;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin \App\Utils\Eloquent\Model
 */
trait HasTeam {
    #[CascadeDelete(false)]
    public function team(): BelongsTo {
        return $this->belongsTo(Team::class);
    }

    public function setTeamAttribute(?Team $team): void {
        $this->team()->associate($team);
    }
}