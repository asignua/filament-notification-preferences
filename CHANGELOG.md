# Changelog

All notable changes to `asignua/filament-notification-preferences` are documented here.

## Unreleased

- First version: a type x channel preference matrix for Filament 5 panels (page, user menu, embeddable Livewire component),
  `NotificationType` declarations (key, label, description, group, channels, per-channel default, `locked`), a
  `NotificationSending` listener that honours the choice at sending time (queued notifications included), fail-open for
  undeclared types, anonymous recipients and read errors, `NotificationPreferences::for($user)->allows()`, a `canManageNotificationPreferences`
  gate, a per-recipient cache with invalidation, Octane-safe scoped state, ten languages.
- Fixes after review: the page slug follows the panel it is asked for (`->slug()`); the user-menu link is hidden on a tenant panel without a
  tenant; a versioned cache key stops a late reader from restoring an answer a save already replaced; type keys that equal another type's
  class name, and unlocked types for subclasses of locked ones, are refused at `register()`; `via()` returning `MailChannel::class` (or the
  database/broadcast class) follows the choice for `mail`; `apc`, `octane` and `session` cache stores are never used; identical type
  declarations from several panels are accepted while conflicting ones get a clear error; the "not offered" cell has real text instead of a
  span `aria-label`; CI also runs on MariaDB 11.4 and Postgres 16.
