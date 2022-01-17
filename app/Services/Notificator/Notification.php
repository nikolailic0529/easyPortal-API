<?php declare(strict_types = 1);

namespace App\Services\Notificator;

use App\Models\User;
use App\Services\I18n\Formatter;
use App\Services\Service;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Notifications\Action;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification as IlluminateNotification;
use ReflectionClass;

use function __;
use function trim;

abstract class Notification extends IlluminateNotification {
    public ?string $timezone = null;

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

    public function getTimezone(): ?string {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage {
        // FIXME Formatter should use Timezone ($formatter->forTimezone())
        $container = Container::getInstance();
        $formatter = $container->make(Formatter::class)
            ->forLocale($this->getLocale())
            ->forTimezone($this->getTimezone());
        $config    = $container->make(Repository::class);
        $service   = Service::getServiceName($this);
        $name      = (new ReflectionClass($this))->getShortName();
        $translate = static function (string $string, array $replacements = []) use ($service, $name): ?string {
            $key        = "notifications.{$service}.{$name}.{$string}";
            $translated = __($key, $replacements);

            if ($key === $translated) {
                $translated = null;
            }

            return $translated;
        };

        return $this->getMailMessage($notifiable, $config, $formatter, $translate);
    }

    /**
     * @param \Closure(string, array<string>): ?string $translate
     */
    protected function getMailMessage(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
    ): MailMessage {
        $replacements = $this->getMailReplacements($notifiable, $config, $formatter, $translate);
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
                static function (MailMessage $message) use ($replacements): MailMessage {
                    return $message->greeting(
                        __('notifications.default.greeting', $replacements),
                    );
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
                static function (MailMessage $message) use ($replacements): MailMessage {
                    return $message->salutation(
                        __('notifications.default.salutation', $replacements),
                    );
                },
            );

        return $message;
    }

    protected function getMailAction(User $notifiable, Repository $config, Formatter $formatter): ?Action {
        return new Action($config->get('app.name'), $config->get('app.url'));
    }

    /**
     * @param \Closure(string, array<string>): ?string $translate
     *
     * @return array<string,scalar|\Stringable>
     */
    protected function getMailReplacements(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
    ): array {
        return [
            'appName'        => trim((string) $config->get('app.name')),
            'userName'       => trim("{$notifiable->given_name} {$notifiable->family_name}"),
            'userGivenName'  => trim($notifiable->given_name),
            'userFamilyName' => trim($notifiable->family_name),
        ];
    }
}
