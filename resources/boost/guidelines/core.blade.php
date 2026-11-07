## Filament Notification Preferences (asignua/filament-notification-preferences)

Users choose per notification type and channel what they receive; a listener on `NotificationSending` cancels what is off, also for queued notifications. Notification classes are never modified.

- Register the plugin on the panel: `->plugin(Asignua\FilamentNotificationPreferences\NotificationPreferencesPlugin::make()->userMenu())`, then `php artisan vendor:publish --tag=filament-notification-preferences-migrations && php artisan migrate && php artisan filament:assets`. The migration is a publishable stub, it is not auto-loaded.
- Declare types in a SERVICE PROVIDER (a queue worker has no panel): `NotificationPreferences::register(NotificationType::make(Foo::class)->key('foo.bar')->label('..')->description('..')->group('..')->channels(['mail', 'database', SmsChannel::class => 'SMS'])->default(false)->locked())`. Give every type a stable `->key()`; the class name is the key otherwise and renaming the class orphans the stored choices.
- Anything not declared is always sent; so are `locked` types, channels the type does not offer, anonymous `Notification::route()` recipients and any error while reading choices.
- `Filament\Notifications\Notification::sendToDatabase()` uses one class for every message and cannot be told apart by type; only `Filament\Notifications\DatabaseNotification` as a whole can be declared.
- Code API: `NotificationPreferences::for($user)->allows($keyOrClassOrNotification, $channel)`, `->matrix()`, `->save([key => [channel => bool]])`, `->forget()`.
- Access: any signed-in user, for their own choices only. Narrow it with the `canManageNotificationPreferences` gate or `->visible(fn ($user) => ...)`; the plugin can embed `Livewire\PreferencesMatrix` in a profile page after `->withoutPage()`.
- Only deviations from the default are stored. The per-recipient cache never uses the `array`/`null` stores.
