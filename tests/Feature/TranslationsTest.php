<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Tests\Feature;

use Asignua\FilamentNotificationPreferences\Tests\TestCase;
use Illuminate\Support\Arr;

class TranslationsTest extends TestCase
{
    private const array LOCALES = ['de', 'en', 'es', 'fr', 'it', 'nl', 'pl', 'pt_BR', 'tr', 'uk'];

    public function test_every_locale_has_the_same_keys_as_english(): void
    {
        $english = $this->keys('en');

        $this->assertNotEmpty($english);

        foreach (self::LOCALES as $locale) {
            $this->assertSame($english, $this->keys($locale), "Locale [{$locale}] is out of step with [en].");
        }
    }

    public function test_every_locale_keeps_the_placeholders_of_english(): void
    {
        foreach ($this->load('en') as $key => $value) {
            foreach (self::LOCALES as $locale) {
                $this->assertSame(
                    $this->placeholders((string) $value),
                    $this->placeholders((string) $this->load($locale)[$key]),
                    "Locale [{$locale}] changed the placeholders of [{$key}].",
                );
            }
        }
    }

    public function test_no_value_is_empty(): void
    {
        foreach (self::LOCALES as $locale) {
            foreach ($this->load($locale) as $key => $value) {
                $this->assertNotSame('', trim((string) $value), "[{$locale}] {$key} is empty.");
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $locale): array
    {
        $path = __DIR__.'/../../resources/lang/'.$locale.'/filament-notification-preferences.php';

        $this->assertFileExists($path);

        return Arr::dot(require $path);
    }

    /**
     * @return list<string>
     */
    private function keys(string $locale): array
    {
        $keys = array_keys($this->load($locale));
        sort($keys);

        return $keys;
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $value): array
    {
        preg_match_all('/:[a-z_]+/', $value, $matches);
        sort($matches[0]);

        return $matches[0];
    }
}
