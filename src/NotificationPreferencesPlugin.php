<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences;

use Asignua\FilamentNotificationPreferences\Pages\NotificationPreferencesPage;
use Asignua\FilamentNotificationPreferences\Support\NotificationType;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Adds the "Notification preferences" page to a panel.
 *
 *     ->plugin(NotificationPreferencesPlugin::make()
 *         ->navigationGroup('Settings')
 *         ->userMenu()
 *         ->types([NotificationType::make(InvoicePaid::class)->label('Invoice paid')]))
 *
 * Types may equally be registered from a service provider with NotificationPreferences::register(): that is the
 * place that also works in a queue worker that has no panel.
 */
class NotificationPreferencesPlugin implements Plugin
{
    public const string ID = 'asignua-filament-notification-preferences';

    /** Gate ability that, when defined, decides who may open the page and save. Undefined = every signed-in user. */
    public const string GATE = 'canManageNotificationPreferences';

    /** @var list<NotificationType> */
    protected array $types = [];

    protected bool $hasPage = true;

    protected bool $inNavigation = true;

    protected bool|string $userMenu = false;

    protected bool|Closure $visible = true;

    protected string|Closure|null $navigationLabel = null;

    protected string|UnitEnum|Closure|null $navigationGroup = null;

    protected string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected ?int $navigationSort = null;

    protected string $slug = 'notification-preferences';

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(static::ID);
    }

    /**
     * The plugin of the given (default: current) panel when it has one, otherwise a bare instance with the defaults (a Livewire
     * component embedded in a page of a panel that did not register the plugin).
     */
    public static function resolve(?Panel $panel = null): static
    {
        $panel ??= Filament::getCurrentPanel();

        if ($panel !== null && $panel->hasPlugin(static::ID)) {
            /** @var static */
            return $panel->getPlugin(static::ID);
        }

        return static::make();
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        if ($this->types !== []) {
            NotificationPreferences::register(...$this->types);
        }

        if ($this->hasPage) {
            $panel->pages([NotificationPreferencesPage::class]);
        }

        // The link points at the page: with `->withoutPage()` its route does not exist and every panel page would fail.
        if ($this->hasPage && $this->userMenu !== false) {
            $panel->userMenuItems([
                'notification-preferences' => Action::make('notification-preferences')
                    ->label(fn (): string => is_string($this->userMenu) ? $this->userMenu : __('filament-notification-preferences::filament-notification-preferences.page.title'))
                    ->icon($this->navigationIcon)
                    ->url(fn (): string => NotificationPreferencesPage::getUrl(panel: $panel->getId()))
                    ->visible(fn (): bool => (!$panel->hasTenancy() || Filament::getTenant() !== null) && $this->canManage(static::currentUser())),
            ]);
        }
    }

    public function boot(Panel $panel): void {}

    /**
     * @param list<NotificationType> $types
     */
    public function types(array $types): static
    {
        $this->types = $types;

        return $this;
    }

    /**
     * Leave the page out: embed the PreferencesMatrix Livewire component in your own profile page instead.
     */
    public function withoutPage(): static
    {
        $this->hasPage = false;

        return $this;
    }

    public function navigation(bool $condition = true): static
    {
        $this->inNavigation = $condition;

        return $this;
    }

    /**
     * Also put a link into the user menu. A string is the label.
     */
    public function userMenu(bool|string $label = true): static
    {
        $this->userMenu = $label;

        return $this;
    }

    /**
     * Who sees the page: a bool or a closure receiving the signed-in user. It narrows the Gate ability, it never widens it.
     */
    public function visible(bool|Closure $condition = true): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function navigationLabel(string|Closure|null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function navigationGroup(string|UnitEnum|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function navigationIcon(string|BackedEnum|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function shouldRegisterNavigation(): bool
    {
        return $this->inNavigation;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel instanceof Closure ? ($this->navigationLabel)() : $this->navigationLabel;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return $this->navigationGroup instanceof Closure ? ($this->navigationGroup)() : $this->navigationGroup;
    }

    public function getNavigationIcon(): string|BackedEnum|null
    {
        return $this->navigationIcon;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * May this user open the page and save? Signed in, the `canManageNotificationPreferences` ability when the
     * application defines one, and the `->visible()` condition. It only ever concerns the user's OWN choices:
     * the recipient is always the signed-in user, never a request parameter.
     */
    public function canManage(?Authenticatable $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (Gate::has(self::GATE) && !Gate::forUser($user)->allows(self::GATE)) {
            return false;
        }

        return $this->visible instanceof Closure ? (bool) ($this->visible)($user) : $this->visible;
    }

    public static function currentUser(): ?Authenticatable
    {
        $panel = Filament::getCurrentPanel();

        return $panel !== null ? Filament::auth()->user() : auth()->user();
    }
}
