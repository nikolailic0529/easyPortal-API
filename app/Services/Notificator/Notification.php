<?php declare(strict_types = 1);

namespace App\Services\Notificator;

use App\Models\User;
use App\Queues;
use App\Services\I18n\Locale;
use App\Services\Service;
use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Action;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as IlluminateNotification;
use LastDragon_ru\LaraASP\Formatter\Formatter;
use LastDragon_ru\LaraASP\Queue\Concerns\WithConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\ConfigurableQueueable;
use ReflectionClass;

use function __;
use function trim;

abstract class Notification extends IlluminateNotification implements ShouldQueue, ConfigurableQueueable {
    use Queueable;
    use WithConfig;

    public function __construct() {
        // empty
    }

    public function getLocale(): ?string {
        return $this->locale;
    }

    public function setLocale(?string $locale): static {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQueueConfig(): array {
        return [
            'queue'       => Queues::NOTIFICATOR,
            'afterCommit' => true,
        ];
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage {
        // FIXME Formatter should use Timezone ($formatter->forTimezone())
        $container    = Container::getInstance();
        $formatter    = $container->make(Formatter::class)
            ->forLocale($this->getLocale() ?? $container->make(Locale::class)->get());
        $config       = $container->make(Repository::class);
        $service      = Service::getServiceName($this);
        $notification = (new ReflectionClass($this))->getShortName();
        $translate    = static function (string $string, array $replacements) use ($service, $notification): ?string {
            $key        = "notifications.{$service}.{$notification}.{$string}";
            $translated = __($key, $replacements);

            if ($key === $translated) {
                $translated = null;
            }

            return $translated;
        };
        $message      = $this->getMailMessage($notifiable, $config, $formatter, $translate);

        return $message;
    }

    protected function getMailMessage(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
    ): MailMessage {
        $replacements = $this->getMailReplacements($notifiable, $config, $formatter);
        $message      = (new MailMessage())
            ->subject($translate('subject', $replacements))
            ->when(
                $translate('level'),
                static function (MailMessage $message, string $level): MailMessage {
                    return $message->level($level);
                },
            )
            ->when(
                $translate('greeting', $replacements),
                static function (MailMessage $message, string $greeting): MailMessage {
                    return $message->greeting($greeting);
                },
            )
            ->when(
                $translate('intro', $replacements),
                static function (MailMessage $message, string $intro): MailMessage {
                    return $message->with($intro);
                },
            )
            ->when(
                $this->getMailAction($notifiable, $config, $formatter),
                static function (MailMessage $message, Action $action): MailMessage {
                    return $message->with($action);
                },
            )
            ->when(
                $translate('outro', $replacements),
                static function (MailMessage $message, string $outro): MailMessage {
                    return $message->with($outro);
                },
            )
            ->when(
                $translate('salutation', $replacements),
                static function (MailMessage $message, string $salutation): MailMessage {
                    return $message->salutation($salutation);
                },
            );

        return $message;
    }

    protected function getMailAction(User $notifiable, Repository $config, Formatter $formatter): ?Action {
        return new Action($config->get('app.name'), $config->get('app.url'));
    }

    /**
     * @return array<string,scalar|\Stringable>
     */
    protected function getMailReplacements(User $notifiable, Repository $config, Formatter $formatter): array {
        return [
            'appName'        => trim((string) $config->get('app.name')),
            'userName'       => trim("{$notifiable->given_name} {$notifiable->family_name}"),
            'userGivenName'  => trim($notifiable->given_name),
            'userFamilyName' => trim($notifiable->family_name),
        ];
    }
}
