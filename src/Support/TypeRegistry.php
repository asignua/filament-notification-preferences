<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Support;

use InvalidArgumentException;

/**
 * The declared notification types. Filled at boot (a service provider or the plugin) and read afterwards, never
 * per request, so it holds nothing that could leak between two Octane requests.
 */
final class TypeRegistry
{
    /** @var array<string, NotificationType> key => type */
    private array $byKey = [];

    /** @var array<class-string, NotificationType> */
    private array $byClass = [];

    /** @var array<string, false|NotificationType> memo of resolve(): a class (or a subclass of one) => its type */
    private array $resolved = [];

    public function register(NotificationType ...$types): void
    {
        foreach ($types as $type) {
            $key = $type->getKey();

            $existing = $this->byKey[$key] ?? null;

            if ($existing !== null && $existing !== $type) {
                if ($existing->isSameDeclarationAs($type)) {
                    // Declared twice identically (two panels with the same types): the first one stays.
                    continue;
                }

                throw new InvalidArgumentException("The notification type key [{$key}] is already registered with a different declaration (class [{$existing->getClass()}], channels, defaults or locked flag differ): declare it once, or give one of them another ->key().");
            }

            if (isset($this->byClass[$type->getClass()]) && $this->byClass[$type->getClass()] !== $type) {
                throw new InvalidArgumentException('The notification class ['.$type->getClass().'] is already registered under the key ['.$this->byClass[$type->getClass()]->getKey().'].');
            }

            $this->guardAmbiguity($type, $key);

            $this->byKey[$key] = $type;
            $this->byClass[$type->getClass()] = $type;
        }

        $this->resolved = [];
    }

    /**
     * A key that is another type's class name (or the reverse) would make one string name two types, and a type
     * for a subclass of a locked notification, left unlocked, would let the subclass be switched off.
     */
    private function guardAmbiguity(NotificationType $type, string $key): void
    {
        foreach ($this->byKey as $otherKey => $other) {
            if ($other === $type) {
                continue;
            }

            if ($key === $other->getClass() || $otherKey === $type->getClass()) {
                throw new InvalidArgumentException("The notification type key [{$key}] clashes with the class name of the type [{$otherKey}] (or the reverse): a string would name two types. Give one of them another ->key().");
            }

            if (is_subclass_of($type->getClass(), $other->getClass())) {
                [$parent, $child] = [$other, $type];
            } elseif (is_subclass_of($other->getClass(), $type->getClass())) {
                [$parent, $child] = [$type, $other];
            } else {
                continue;
            }

            if ($parent->isLocked() && !$child->isLocked()) {
                throw new InvalidArgumentException('The notification class ['.$child->getClass().'] extends the locked ['.$parent->getClass().'] and would be left switchable: lock it too, or do not declare it (it then follows its parent).');
            }
        }
    }

    /**
     * @return array<string, NotificationType>
     */
    public function all(): array
    {
        return $this->byKey;
    }

    public function find(string $keyOrClass): ?NotificationType
    {
        return $this->byKey[$keyOrClass] ?? $this->resolveClass($keyOrClass);
    }

    /**
     * The type that covers a notification class: its own, otherwise the NEAREST declared ancestor, so a subclass
     * of a declared notification follows the choices made for its parent. Unknown class: null (it is sent).
     */
    public function resolveClass(string $class): ?NotificationType
    {
        if (isset($this->byClass[$class])) {
            return $this->byClass[$class];
        }

        $cached = $this->resolved[$class] ?? null;

        if ($cached !== null) {
            return $cached === false ? null : $cached;
        }

        $found = null;

        if (class_exists($class)) {
            // class_parents() lists the nearest parent first.
            foreach (class_parents($class) ?: [] as $parent) {
                if (isset($this->byClass[$parent])) {
                    $found = $this->byClass[$parent];

                    break;
                }
            }
        }

        $this->resolved[$class] = $found ?? false;

        return $found;
    }

    public function flush(): void
    {
        $this->byKey = [];
        $this->byClass = [];
        $this->resolved = [];
    }
}
