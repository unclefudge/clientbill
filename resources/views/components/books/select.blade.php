@props([
    'options' => [],
    'value' => null,
    'model' => null,
    'action' => null,
    'actionArgs' => [],
    'placeholder' => 'Select an option',
    'allowEmpty' => false,
    'emptyLabel' => null,
    'disabled' => false,
    'width' => 'w-full',
])

@php
    $normalisedOptions = collect($options)->map(function ($label, $optionValue) {
        if (is_array($label) && array_key_exists('value', $label)) {
            return [
                'value' => $label['value'],
                'label' => (string) ($label['label'] ?? $label['value']),
            ];
        }

        return [
            'value' => $optionValue,
            'label' => (string) $label,
        ];
    })->values();

    $selected = $normalisedOptions->first(
        fn (array $option) => (string) $option['value'] === (string) ($value ?? '')
    );

    $displayLabel = $selected['label'] ?? ($emptyLabel ?? $placeholder);
    $showPlaceholderStyle = ! $selected && blank($value);

    $wireExpression = function ($optionValue) use ($model, $action, $actionArgs) {
        if ($model) {
            return '$set(' . \Illuminate\Support\Js::from($model)->toHtml() . ', ' . \Illuminate\Support\Js::from($optionValue)->toHtml() . ')';
        }

        if ($action) {
            $arguments = collect([...$actionArgs, $optionValue])
                ->map(fn ($argument) => \Illuminate\Support\Js::from($argument)->toHtml())
                ->implode(', ');

            return $action . '(' . $arguments . ')';
        }

        return null;
    };
@endphp

<div {{ $attributes->class(['relative', $width]) }}>
    <x-filament::dropdown placement="bottom-start" teleport>
        <x-slot name="trigger">
            <button
                type="button"
                @disabled($disabled)
                class="flex min-h-10 w-full items-center justify-between gap-3 rounded-lg bg-white px-3 py-2 text-left text-sm shadow-sm ring-1 ring-gray-950/10 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white/5 dark:ring-white/20 dark:hover:bg-white/10"
            >
                <span @class([
                    'min-w-0 flex-1 truncate',
                    'text-gray-400 dark:text-gray-500' => $showPlaceholderStyle,
                    'text-gray-950 dark:text-white' => ! $showPlaceholderStyle,
                ])>
                    {{ $displayLabel }}
                </span>
                <x-heroicon-m-chevron-down class="h-4 w-4 shrink-0 text-gray-400" />
            </button>
        </x-slot>

        <div class="max-h-72 min-w-56 overflow-y-auto py-1">
            @if ($allowEmpty)
                @php
                    $emptyExpression = $wireExpression(null);
                @endphp

                <button
                    type="button"
                    x-on:click="close($event)"
                    wire:click="{{ $emptyExpression }}"
                    class="flex w-full items-center justify-between gap-4 px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none dark:text-gray-200 dark:hover:bg-white/5 dark:focus:bg-white/5"
                >
                    <span class="min-w-0 flex-1 truncate">{{ $emptyLabel ?? $placeholder }}</span>
                    @if (blank($value))
                        <x-heroicon-m-check class="h-4 w-4 shrink-0 text-primary-500" />
                    @endif
                </button>
            @endif

            @foreach ($normalisedOptions as $option)
                @php
                    $optionExpression = $wireExpression($option['value']);
                    $isSelected = (string) $option['value'] === (string) ($value ?? '');
                @endphp

                <button
                    type="button"
                    x-on:click="close($event)"
                    wire:click="{{ $optionExpression }}"
                    class="flex w-full items-center justify-between gap-4 px-3 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50 focus:bg-gray-50 focus:outline-none dark:text-gray-200 dark:hover:bg-white/5 dark:focus:bg-white/5"
                >
                    <span class="min-w-0 flex-1 truncate">{{ $option['label'] }}</span>
                    @if ($isSelected)
                        <x-heroicon-m-check class="h-4 w-4 shrink-0 text-primary-500" />
                    @endif
                </button>
            @endforeach
        </div>
    </x-filament::dropdown>
</div>
