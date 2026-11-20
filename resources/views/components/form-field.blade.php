{{-- Label + control (slot) + hint + validation error for public forms. --}}
@props(['for', 'label', 'required' => false, 'hint' => null, 'error' => null])

@php($errorKey = $error ?? $for)

<div {{ $attributes }}>
    <label for="{{ $for }}" class="form-label">
        {{ $label }}
        @if ($required === true)
            <span class="text-red-600" aria-hidden="true">*</span>
        @elseif ($required)
            <span class="text-red-600" aria-hidden="true" x-show="{{ $required }}" x-cloak>*</span>
        @endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p id="{{ $for }}-hint" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($errorKey)
        <p id="{{ $for }}-error" class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
