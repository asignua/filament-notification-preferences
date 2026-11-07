<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Support;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One notification type a user can tune: which class it covers, how it is presented and on which channels it is offered.
 *
 *     NotificationType::make(InvoicePaid::class)
 *         ->key('billing.invoice-paid')
 *         ->label('Invoice paid')
 *         ->description('Sent when a customer pays one of your invoices.')
 *         ->group('Billing')
 *         ->channels(['mail', 'database', SmsChannel::class => 'SMS'])
 *         ->default(['mail' => true, 'database' => true, SmsChannel::class => false])
 *
 * The notification class itself is never touched. Labels may be closures: they are called when the page is
 * rendered, so the translation follows the user's locale instead of the locale of the boot.
 */
final class NotificationType
{
    /** Longest key the table stores (the column is a string(150)). */
    public const int MAX_KEY_LENGTH = 150;

    /** Channel names that need no label from the developer. */
    public const array BUILT_IN_CHANNELS = ['mail', 'database', 'broadcast'];

    private ?string $key = null;

    private string|Closure|null $label = null;

    private string|Closure|null $description = null;

    private string|Closure|null $group = null;

    /** @var array<string, Closure|string|null> channel => label (null = the built-in or derived label) */
    private array $channels = ['mail' => null, 'database' => null];

    /** @var array<string, bool>|bool */
    private bool|array $default = true;

    private bool $locked = false;

    /**
     * @param class-string $class
     */
    private function __construct(private readonly string $class) {}

    /**
     * @param class-string $class the notification class (a subclass of it is covered by this type too)
     */
    public static function make(string $class): self
    {
        return new self($class);
    }

    /**
     * A stable identifier stored with the choices. Without it the class name is the key, which breaks the
     * stored choices the day the class is renamed.
     */
    public function key(string $key): self
    {
        if ($key === '' || mb_strlen($key) > self::MAX_KEY_LENGTH) {
            throw new InvalidArgumentException('A notification type key must be 1-'.self::MAX_KEY_LENGTH.' characters long.');
        }

        $this->key = $key;

        return $this;
    }

    public function label(string|Closure $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function description(string|Closure|null $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function group(string|Closure $group): self
    {
        $this->group = $group;

        return $this;
    }

    /**
     * The channels the user can switch. A list value is a channel name (`mail`, `database`, `broadcast`);
     * a string key is a channel as `via()` returns it (a class name for a custom channel) with its label.
     *
     * @param array<int|string, Closure|string|null> $channels
     */
    public function channels(array $channels): self
    {
        $normalized = [];

        foreach ($channels as $channel => $label) {
            if (is_int($channel)) {
                $channel = $label;
                $label = null;
            }

            if (!is_string($channel) || $channel === '' || mb_strlen($channel) > self::MAX_KEY_LENGTH) {
                throw new InvalidArgumentException('A channel is the string `via()` returns (a name or a class), 1-'.self::MAX_KEY_LENGTH.' characters.');
            }

            $normalized[$channel] = $label;
        }

        if ($normalized === []) {
            throw new InvalidArgumentException('A notification type needs at least one channel.');
        }

        $this->channels = $normalized;

        return $this;
    }

    /**
     * `true` / `false` for all channels, or a map channel => bool (a channel left out stays on).
     *
     * @param array<string, bool>|bool $default
     */
    public function default(bool|array $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Mandatory: always delivered, shown to the user as such, and not changeable even by a forged request.
     */
    public function locked(bool $locked = true): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    public function getKey(): string
    {
        $key = $this->key ?? $this->class;

        // The class name is long for deep namespaces; refuse early, not as a truncated column on the first save.
        if (mb_strlen($key) > self::MAX_KEY_LENGTH) {
            throw new InvalidArgumentException("The class name [{$key}] is longer than ".self::MAX_KEY_LENGTH.' characters: give the type a short ->key().');
        }

        return $key;
    }

    public function getLabel(): string
    {
        return $this->resolve($this->label) ?? Str::headline(class_basename($this->class));
    }

    public function getDescription(): ?string
    {
        return $this->resolve($this->description);
    }

    public function getGroup(): string
    {
        return $this->resolve($this->group) ?? (string) __('filament-notification-preferences::filament-notification-preferences.group.general');
    }

    /**
     * @return list<string>
     */
    public function getChannels(): array
    {
        return array_keys($this->channels);
    }

    public function offers(string $channel): bool
    {
        return array_key_exists($channel, $this->channels);
    }

    public function getChannelLabel(string $channel): ?string
    {
        if (!$this->offers($channel)) {
            return null;
        }

        return $this->resolve($this->channels[$channel]) ?? self::defaultChannelLabel($channel);
    }

    public static function defaultChannelLabel(string $channel): string
    {
        if (in_array($channel, self::BUILT_IN_CHANNELS, true)) {
            return (string) __('filament-notification-preferences::filament-notification-preferences.channels.'.$channel);
        }

        return Str::headline(class_basename($channel));
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function getDefault(string $channel): bool
    {
        return is_bool($this->default) ? $this->default : ($this->default[$channel] ?? true);
    }

    private function resolve(string|Closure|null $value): ?string
    {
        $resolved = $value instanceof Closure ? $value() : $value;

        return is_string($resolved) && $resolved !== '' ? $resolved : null;
    }
}
