# Changelog

All notable changes to `asignua/filament-notification-preferences` are documented here.

## Unreleased

- First version: a type x channel preference matrix for Filament 5 panels (page, user menu, embeddable Livewire component),
  `NotificationType` declarations (key, label, description, group, channels, per-channel default, `locked`), a
  `NotificationSending` listener that honours the choice at sending time (queued notifications included), fail-open for
  undeclared types, anonymous recipients and read errors, `NotificationPreferences::for($user)->allows()`, a `canManageNotificationPreferences`
  gate, a per-recipient cache with invalidation, Octane-safe scoped state, ten languages.
