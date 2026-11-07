<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\Livewire\PreferencesMatrix;
use Asignua\FilamentNotificationPreferences\Models\NotificationPreference;
use Asignua\FilamentNotificationPreferences\NotificationPreferences;
use Asignua\FilamentNotificationPreferences\Support\MatrixLayout;
use Asignua\FilamentNotificationPreferences\Tests\Support\Sandbox;
use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionProperty;
use Workbench\App\Channels\SinkChannel;

class MatrixComponentTest extends TestCase
{
    use Sandbox;

    private function t(string $key): string
    {
        return MatrixLayout::id('t', $key);
    }

    private function c(string $channel): string
    {
        return MatrixLayout::id('c', $channel);
    }

    public function test_it_renders_the_types_grouped_with_their_descriptions(): void
    {
        Livewire::test(PreferencesMatrix::class)
            ->assertSee('New comment')
            ->assertSee('Someone commented on a record you follow.')
            ->assertSee('Comments')
            ->assertSee('Digests')
            ->assertSee('Security alerts')
            ->assertSee(__('filament-notification-preferences::filament-notification-preferences.enable_all'))
            ->assertSee(__('filament-notification-preferences::filament-notification-preferences.locked'));
    }

    public function test_it_starts_from_the_defaults(): void
    {
        $component = Livewire::test(PreferencesMatrix::class);

        $component
            ->assertSet('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), true)
            ->assertSet('matrix.'.$this->t('comments.mention').'.'.$this->c('mail'), false)
            ->assertSet('matrix.'.$this->t('comments.mention').'.'.$this->c('database'), true)
            ->assertSet('matrix.'.$this->t('billing.invoice-paid').'.'.$this->c(SinkChannel::class), false)
            ->assertSet('matrix.'.$this->t('security.alert').'.'.$this->c('mail'), true);
    }

    public function test_a_channel_the_type_lacks_has_no_checkbox(): void
    {
        $html = Livewire::test(PreferencesMatrix::class)->html();

        $this->assertStringNotContainsString('matrix.'.$this->t('digests.weekly').'.'.$this->c('database'), $html);
        $this->assertStringContainsString('matrix.'.$this->t('digests.weekly').'.'.$this->c('mail'), $html);
        $this->assertStringContainsString(__('filament-notification-preferences::filament-notification-preferences.unavailable'), $html);
    }

    public function test_the_locked_row_is_rendered_checked_and_disabled_without_a_binding(): void
    {
        $html = Livewire::test(PreferencesMatrix::class)->html();

        $this->assertStringNotContainsString('matrix.'.$this->t('security.alert'), $html);
        $this->assertMatchesRegularExpression('/<input[^>]*type="checkbox"[^>]*disabled[^>]*>/', $html);
        $this->assertStringContainsString(__('filament-notification-preferences::filament-notification-preferences.locked_explanation'), $html);
    }

    public function test_every_checkbox_has_an_accessible_name_and_the_table_has_headers(): void
    {
        $html = Livewire::test(PreferencesMatrix::class)->html();

        preg_match_all('/<input[^>]*type="checkbox"[^>]*>/', $html, $inputs);
        $this->assertNotEmpty($inputs[0]);

        foreach ($inputs[0] as $input) {
            $this->assertMatchesRegularExpression('/aria-label="[^"]+"/', $input, $input);
        }

        $this->assertStringContainsString('scope="col"', $html);
        $this->assertStringContainsString('scope="row"', $html);
        $this->assertStringContainsString('<caption', $html);
    }

    public function test_save_stores_only_the_deviations_of_the_signed_in_user(): void
    {
        $other = $this->makeUser('Other');
        $me = auth()->user();

        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), false)
            ->set('matrix.'.$this->t('digests.weekly').'.'.$this->c('mail'), true)
            ->call('save')
            ->assertHasNoErrors();

        $rows = NotificationPreference::query()->orderBy('type_key')->get();

        $this->assertCount(2, $rows);
        $this->assertSame([$me->getMorphClass()], $rows->pluck('notifiable_type')->unique()->all());
        $this->assertSame([(string) $me->getKey()], $rows->pluck('notifiable_id')->unique()->all());
        $this->assertSame(0, NotificationPreference::query()->where('notifiable_id', (string) $other->getKey())->count());
    }

    public function test_the_saved_state_survives_a_reload(): void
    {
        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.'.$this->t('comments.new').'.'.$this->c('database'), false)
            ->call('save');

        Livewire::test(PreferencesMatrix::class)
            ->assertSet('matrix.'.$this->t('comments.new').'.'.$this->c('database'), false)
            ->assertSet('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), true);
    }

    public function test_a_forged_payload_cannot_change_a_locked_type(): void
    {
        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.'.$this->t('security.alert').'.'.$this->c('mail'), false)
            ->set('matrix.'.$this->t('security.alert').'.'.$this->c('database'), false)
            ->call('save');

        $this->assertSame(0, NotificationPreference::query()->count());
        $this->assertTrue(NotificationPreferences::for(auth()->user())->allows('security.alert', 'mail'));
    }

    public function test_a_forged_payload_with_unknown_ids_wrong_channels_and_odd_values_stores_nothing_extra(): void
    {
        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.tdeadbeef0000.cdeadbeef0000', false)
            ->set('matrix.'.$this->t('digests.weekly').'.'.$this->c('database'), true)
            ->set('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), ['x' => 1])
            ->set('matrix.'.$this->t('comments.new').'.'.$this->c('database'), 'definitely')
            ->call('save');

        $this->assertSame(0, NotificationPreference::query()->count());
    }

    public function test_a_string_boolean_from_the_wire_is_understood(): void
    {
        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), 'false')
            ->call('save');

        $this->assertFalse(NotificationPreferences::for(auth()->user())->allows('comments.new', 'mail'));
    }

    public function test_the_component_has_no_property_that_names_a_user(): void
    {
        $properties = array_map(
            fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(PreferencesMatrix::class))->getProperties(ReflectionProperty::IS_PUBLIC),
        );

        $this->assertSame(['matrix'], array_values(array_diff($properties, ['__id', '__name'])));
    }

    public function test_another_users_rows_are_untouched_by_a_save(): void
    {
        $other = $this->makeUser('Other');
        NotificationPreferences::for($other)->save(['comments.new' => ['mail' => false]]);

        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), false)
            ->call('save');

        $this->assertSame(1, NotificationPreference::query()->where('notifiable_id', (string) $other->getKey())->count());
        $this->assertFalse(NotificationPreferences::for($other)->allows('comments.new', 'mail'));
    }

    public function test_the_group_toggles_flip_the_unlocked_cells_of_that_group_only(): void
    {
        $group = MatrixLayout::id('g', 'Comments');

        $component = Livewire::test(PreferencesMatrix::class)
            ->call('setGroup', $group, false)
            ->assertSet('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), false)
            ->assertSet('matrix.'.$this->t('comments.new').'.'.$this->c('database'), false)
            ->assertSet('matrix.'.$this->t('comments.mention').'.'.$this->c('database'), false)
            // another group is not touched
            ->assertSet('matrix.'.$this->t('billing.invoice-paid').'.'.$this->c('mail'), true);

        // nothing is stored until Save
        $this->assertSame(0, NotificationPreference::query()->count());

        $component->call('setGroup', $group, true)
            ->assertSet('matrix.'.$this->t('comments.mention').'.'.$this->c('mail'), true)
            ->call('save');

        $this->assertTrue(NotificationPreferences::for(auth()->user())->allows('comments.mention', 'mail'));
    }

    public function test_a_group_toggle_never_unlocks_a_required_type(): void
    {
        $group = MatrixLayout::id('g', 'Security');

        Livewire::test(PreferencesMatrix::class)
            ->call('setGroup', $group, false)
            ->call('save');

        $this->assertSame(0, NotificationPreference::query()->count());
    }

    public function test_an_unknown_group_changes_nothing(): void
    {
        Livewire::test(PreferencesMatrix::class)
            ->call('setGroup', 'gnonexistent', false)
            ->assertSet('matrix.'.$this->t('comments.new').'.'.$this->c('mail'), true);
    }

    public function test_it_says_so_when_nothing_is_declared(): void
    {
        NotificationPreferences::types()->flush();

        Livewire::test(PreferencesMatrix::class)->assertSee(__('filament-notification-preferences::filament-notification-preferences.empty'));
    }

    public function test_type_keys_with_dots_and_class_names_are_safe_in_property_paths(): void
    {
        NotificationPreferences::types()->flush();
        NotificationPreferences::register(
            \Asignua\FilamentNotificationPreferences\Support\NotificationType::make(\Workbench\App\Notifications\NewComment::class)->channels(['mail', 'App\\Channels\\Sms' => 'SMS']),
        );

        Livewire::test(PreferencesMatrix::class)
            ->set('matrix.'.$this->t(\Workbench\App\Notifications\NewComment::class).'.'.$this->c('App\\Channels\\Sms'), false)
            ->call('save');

        $this->assertFalse(NotificationPreferences::for(auth()->user())->allows(\Workbench\App\Notifications\NewComment::class, 'App\\Channels\\Sms'));
    }
}
