<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Models\User;
use App\Services\Notificator\Notification;
use Illuminate\Contracts\Config\Repository;

trait NotifyUsers {
    protected function notify(Repository $config, Notification $notification): void {
        $users = User::query()
            ->whereIn((new User())->getKeyName(), $config->get('ep.maintenance.notify.users'))
            ->get();

        foreach ($users as $user) {
            $user->notify($notification);
        }
    }
}
