<?php declare(strict_types = 1);

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Access\Gate as ContractGate;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function boot(ContractGate $gate, Gate $auth): void {
        $gate->before([$auth, 'before']);
        $gate->after([$auth, 'after']);
    }
}
