@props([
    'name',
    'value' => null,
    'id' => null,
    'placeholder' => 'DD-MM-YYYY',
    'required' => false,
    'hint' => 'Format: DD-MM-YYYY (contoh: 14-05-2008)',
])

@php
    $inputId = $id ?? 'input-' . str_replace(['[', ']'], '-', $name);

    $formatted = old(
        $name,
        $value instanceof \DateTimeInterface
            ? $value->format('d-m-Y')
            : (is_string($value) && trim($value) !== '' ? $value : '')
    );

    if (is_string($formatted) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', trim($formatted), $matches)) {
        $formatted = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
    }
@endphp

<div
    x-data="{
        fp: null,
        init() {
            this.$nextTick(() => {
                if (typeof window.flatpickr === 'function') {
                    this.fp = window.flatpickr(this.$refs.dateField, {
                        dateFormat: 'd-m-Y',
                        allowInput: true,
                        disableMobile: true,
                        defaultDate: this.$refs.dateField.value || null,
                    });
                }
            });
        },
        handleInput(e) {
            if (e.inputType && e.inputType.startsWith('delete')) {
                return;
            }
            let digits = e.target.value.replace(/\D/g, '').slice(0, 8);
            let out = digits;
            if (digits.length > 4) {
                out = digits.slice(0, 2) + '-' + digits.slice(2, 4) + '-' + digits.slice(4);
            } else if (digits.length > 2) {
                out = digits.slice(0, 2) + '-' + digits.slice(2);
            }
            if (e.target.value !== out) {
                e.target.value = out;
            }
        }
    }"
    class="relative"
>
    <div class="relative">
        <input
            x-ref="dateField"
            type="text"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ $formatted }}"
            placeholder="{{ $placeholder }}"
            maxlength="10"
            inputmode="numeric"
            autocomplete="off"
            @if($required) required @endif
            @input="handleInput($event)"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pr-11 pl-4 text-sm tabular-nums text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        <button
            type="button"
            @click="fp ? fp.toggle() : $refs.dateField.focus()"
            aria-label="Pilih tanggal kalender"
            class="absolute top-1/2 right-2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18" />
            </svg>
        </button>
    </div>
    @if($hint)
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $hint }}</p>
    @endif
</div>
