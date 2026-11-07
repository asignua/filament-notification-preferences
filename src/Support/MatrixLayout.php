<?php

declare(strict_types=1);

namespace Asignua\FilamentNotificationPreferences\Support;

/**
 * The shape the page renders: groups of types against one shared list of channel columns.
 *
 * Livewire addresses a checkbox by a property path (`matrix.<type>.<channel>`), and keys and channels may hold
 * dots, backslashes (a class name) or anything else a path cannot carry. So the page speaks in short opaque ids
 * derived from them, and this class maps the ids back on the server. A forged id simply finds nothing.
 */
final class MatrixLayout
{
    /**
     * @param list<array{id: string, name: string, label: string}>                                                                                                                                                            $channels
     * @param list<array{id: string, label: string, types: list<array{id: string, key: string, label: string, description: ?string, locked: bool, channels: array<string, array{id: string, name: string, label: string}>}>}> $groups
     */
    private function __construct(public readonly array $channels, public readonly array $groups) {}

    public static function build(TypeRegistry $registry): self
    {
        $channels = [];
        $groups = [];

        foreach ($registry->all() as $key => $type) {
            $typeChannels = [];

            foreach ($type->getChannels() as $channel) {
                $id = self::id('c', $channel);
                $label = (string) $type->getChannelLabel($channel);

                // The column header is the label of the FIRST type that declares the channel; per-row labels
                // (aria) use the type's own wording.
                $channels[$channel] ??= ['id' => $id, 'name' => $channel, 'label' => $label];
                $typeChannels[$channel] = ['id' => $id, 'name' => $channel, 'label' => $label];
            }

            $groupLabel = $type->getGroup();
            $groupId = self::id('g', $groupLabel);

            $groups[$groupId] ??= ['id' => $groupId, 'label' => $groupLabel, 'types' => []];
            $groups[$groupId]['types'][] = [
                'id' => self::id('t', $key),
                'key' => $key,
                'label' => $type->getLabel(),
                'description' => $type->getDescription(),
                'locked' => $type->isLocked(),
                'channels' => $typeChannels,
            ];
        }

        return new self(array_values($channels), array_values($groups));
    }

    public static function id(string $prefix, string $value): string
    {
        return $prefix.substr(sha1($value), 0, 12);
    }

    /**
     * @param array<string, array<string, bool>> $matrix type key => channel => on
     *
     * @return array<string, array<string, bool>> type id => channel id => on
     */
    public function toIds(array $matrix): array
    {
        $byId = [];

        foreach ($this->groups as $group) {
            foreach ($group['types'] as $type) {
                foreach ($type['channels'] as $channel) {
                    if (isset($matrix[$type['key']][$channel['name']])) {
                        $byId[$type['id']][$channel['id']] = (bool) $matrix[$type['key']][$channel['name']];
                    }
                }
            }
        }

        return $byId;
    }

    /**
     * Back from what the page submitted to type key => channel => bool. Unknown ids and non-boolean values drop out here;
     * locked types and channels a type lacks are dropped by UserPreferences::save().
     *
     * @param array<array-key, mixed> $submitted
     *
     * @return array<string, array<string, bool>>
     */
    public function fromIds(array $submitted): array
    {
        $wanted = [];

        foreach ($this->groups as $group) {
            foreach ($group['types'] as $type) {
                $row = $submitted[$type['id']] ?? null;

                if (!is_array($row)) {
                    continue;
                }

                foreach ($type['channels'] as $channel) {
                    $value = $row[$channel['id']] ?? null;
                    $bool = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

                    if ($bool !== null) {
                        $wanted[$type['key']][$channel['name']] = $bool;
                    }
                }
            }
        }

        return $wanted;
    }

    /**
     * Sets a whole group on or off in the submitted state, skipping locked types.
     *
     * @param array<string, array<string, bool>> $state
     *
     * @return array<string, array<string, bool>>
     */
    public function withGroup(array $state, string $groupId, bool $enabled): array
    {
        foreach ($this->groups as $group) {
            if ($group['id'] !== $groupId) {
                continue;
            }

            foreach ($group['types'] as $type) {
                if ($type['locked']) {
                    continue;
                }

                foreach ($type['channels'] as $channel) {
                    $state[$type['id']][$channel['id']] = $enabled;
                }
            }
        }

        return $state;
    }
}
