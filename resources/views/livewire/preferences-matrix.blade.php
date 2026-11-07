@php($t = fn (string $key, array $replace = []) => __('filament-notification-preferences::filament-notification-preferences.'.$key, $replace))

<div class="npref">
    @if ($layout->groups === [])
        <x-filament::section>
            <p class="npref-empty">{{ $t('empty') }}</p>
        </x-filament::section>
    @else
        <form wire:submit="save" class="npref-form">
            <p class="npref-intro">{{ $t('intro') }}</p>

            <div class="npref-scroll">
                <table class="npref-table">
                    <caption class="npref-sr">{{ $t('page.title') }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="npref-type-col">{{ $t('column.type') }}</th>
                            @foreach ($layout->channels as $channel)
                                <th scope="col" class="npref-channel-col">{{ $channel['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    @foreach ($layout->groups as $group)
                        <tbody wire:key="npref-group-{{ $group['id'] }}">
                            <tr class="npref-group">
                                <th scope="rowgroup" colspan="{{ count($layout->channels) + 1 }}">
                                    <span class="npref-group-bar">
                                        <span class="npref-group-title">{{ $group['label'] }}</span>
                                        <span class="npref-group-actions">
                                            <x-filament::button
                                                type="button"
                                                size="xs"
                                                color="gray"
                                                wire:click="setGroup('{{ $group['id'] }}', true)"
                                                :aria-label="$t('enable_all_in', ['group' => $group['label']])"
                                            >
                                                {{ $t('enable_all') }}
                                            </x-filament::button>
                                            <x-filament::button
                                                type="button"
                                                size="xs"
                                                color="gray"
                                                wire:click="setGroup('{{ $group['id'] }}', false)"
                                                :aria-label="$t('disable_all_in', ['group' => $group['label']])"
                                            >
                                                {{ $t('disable_all') }}
                                            </x-filament::button>
                                        </span>
                                    </span>
                                </th>
                            </tr>

                            @foreach ($group['types'] as $type)
                                <tr wire:key="npref-type-{{ $type['id'] }}" class="npref-row">
                                    <th scope="row" class="npref-type">
                                        <span class="npref-type-label">
                                            {{ $type['label'] }}
                                            @if ($type['locked'])
                                                <span class="npref-locked" id="npref-lock-{{ $type['id'] }}">{{ $t('locked') }}</span>
                                            @endif
                                        </span>
                                        @if ($type['description'])
                                            <span class="npref-desc" id="npref-desc-{{ $type['id'] }}">{{ $type['description'] }}</span>
                                        @endif
                                        @if ($type['locked'])
                                            <span class="npref-desc">{{ $t('locked_explanation') }}</span>
                                        @endif
                                    </th>

                                    @foreach ($layout->channels as $channel)
                                        @php($cell = $type['channels'][$channel['name']] ?? null)
                                        <td class="npref-cell" data-label="{{ $channel['label'] }}">
                                            @if ($cell === null)
                                                <span class="npref-na" title="{{ $t('unavailable') }}">
                                                    <span aria-hidden="true">&ndash;</span>
                                                    <span class="npref-sr">{{ $t('unavailable') }}</span>
                                                </span>
                                            @elseif ($type['locked'])
                                                <x-filament::input.checkbox
                                                    :checked="true"
                                                    disabled
                                                    :aria-label="$t('cell_label', ['type' => $type['label'], 'channel' => $cell['label']])"
                                                    aria-describedby="npref-lock-{{ $type['id'] }}"
                                                />
                                            @else
                                                <x-filament::input.checkbox
                                                    wire:model="matrix.{{ $type['id'] }}.{{ $cell['id'] }}"
                                                    :aria-label="$t('cell_label', ['type' => $type['label'], 'channel' => $cell['label']])"
                                                />
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>

            <div class="npref-footer">
                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="save">
                    {{ $t('save') }}
                </x-filament::button>
            </div>
        </form>
    @endif
</div>
