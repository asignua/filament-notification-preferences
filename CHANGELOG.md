# Changelog

All notable changes to `asignua/filament-notification-preferences` are documented here.

## v1.1.0 - 2026-11-10

- Requires Filament ^5.11.2; no deprecated Filament API was in use, so no code changes.

## v1.0.0 - 2026-11-09

- A notification type x channel preference matrix for Filament 5 panels: a page, an optional user-menu link
  (`->userMenu()`, not available with `->withoutPage()`) and an embeddable Livewire component; the page slug follows
  `->slug()` and the link is hidden on a tenant panel without a tenant.
- `NotificationType` declarations (key, label, description, group, channels, per-channel default, `locked`) without touching
  the notification classes. Keys that equal another type's class name, and unlocked types for subclasses of locked ones, are
  refused at `register()`; identical declarations from several panels are accepted, conflicting ones get a clear error.
- A `NotificationSending` listener honours the choice at sending time, queued notifications included. `via()` returning
  `MailChannel::class` (or the database/broadcast class) follows the choice for `mail`.
- Fail-open: undeclared types, anonymous recipients and read errors still send. Locked types cannot be switched off.
- `NotificationPreferences::for($user)->allows()` and the `canManageNotificationPreferences` gate.
- Per-recipient cache with invalidation (versioned key, so a late reader cannot restore a replaced answer); `apc`, `octane`
  and `session` stores are never used. Octane-safe scoped state.
- Ten languages: English, Ukrainian, Polish, German, French, Spanish, Italian, Dutch, Brazilian Portuguese, Turkish.
- CI runs on SQLite, MariaDB 11.4 and Postgres 16.
