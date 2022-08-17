<?php declare(strict_types = 1);

namespace App\Services\Notificator;

use App\Models\User;
use App\Services\I18n\CurrentLocale;
use App\Services\I18n\CurrentTimezone;
use App\Services\I18n\Formatter;
use App\Services\Service;
use App\Utils\Cast;
use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container as ContainerContract;
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
        $container = $this->getContainer();
        $formatter = $container->make(Formatter::class)
            ->forLocale($this->getPreferredLocale($notifiable))
            ->forTimezone($this->getPreferredTimezone($notifiable));
        $config    = $container->make(Repository::class);
        $service   = Service::getServiceName($this) ?? 'App';
        $name      = (new ReflectionClass($this))->getShortName();
        $translate = static function (string $string, array $replacements = []) use ($service, $name): ?string {
            $key        = "notifications.{$service}.{$name}.{$string}";
            $translated = Cast::toString(__($key, $replacements));

            if ($key === $translated) {
                $translated = null;
            }

            return $translated;
        };

        return $this->getMailMessage($notifiable, $config, $formatter, $translate);
    }

    /**
     * @param Closure(string, array<string, scalar>): ?string $translate
     */
    protected function getMailMessage(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
    ): MailMessage {
        $replacements = $this->getMailReplacements($notifiable, $config, $formatter, $translate);
        $message      = new MailMessage();

        // Subject
        $subject = $translate('subject', $replacements);

        if ($subject) {
            $message = $message->subject($subject);
        }

        // Level
        $level = $translate('level', $replacements);

        if ($level) {
            $message = $message->level($level);
        }

        // Greeting
        $greeting = $translate('greeting', $replacements)
            ?: __('notifications.default.greeting', $replacements);
        $message  = $message->greeting($greeting);

        // Intro
        $intro = $translate('intro', $replacements);

        if ($intro) {
            $message = $message->with($intro);
        }

        // Action
        $action = $this->getMailAction($notifiable, $config, $formatter, $translate, $replacements);

        if ($action) {
            $message = $message->with($action);
        }

        // Outro
        $outro = $translate('outro', $replacements);

        if ($outro) {
            $message = $message->with($outro);
        }

        // Salutation
        $salutation = $translate('salutation', $replacements)
            ?: __('notifications.default.salutation', $replacements);
        $message    = $message->salutation($salutation);

        return $message;
    }

    /**
     * @param Closure(string, array<string,scalar>): ?string $translate
     * @param array<string,scalar>                           $replacements
     */
    protected function getMailAction(
        User $notifiable,
        Repository $config,
        Formatter $formatter,
        Closure $translate,
        array $replacements,
    ): ?Action {
        return null;
    }

    /**
     * @param Closure(string, array<string,scalar>): ?string $translate
     *
     * @return array<string,scalar>
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
            'userGivenName'  => trim("{$notifiable->given_name}"),
            'userFamilyName' => trim("{$notifiable->family_name}"),
        ];
    }

    protected function getPreferredLocale(User $notifiable): ?string {
        return $this->getLocale()
            ?? $notifiable->preferredLocale()
            ?? $this->getContainer()->make(CurrentLocale::class)->get();
    }

    protected function getPreferredTimezone(User $notifiable): ?string {
        return $this->getTimezone()
            ?? $notifiable->preferredTimezone()
            ?? $this->getContainer()->make(CurrentTimezone::class)->get();
    }

    protected function getContainer(): ContainerContract {
        return Container::getInstance();
    }
}
