@props([
    'model',
    'options' => [],
    'selected' => '',
    'placeholder' => '',
    'id' => null,
    'invalid' => false,
    'disabled' => false,
])

@php
    $uid = $id ?: 'ss-' . \Illuminate\Support\Str::slug($model, '-');
    $signature = md5(json_encode([$options, (string) $selected, $disabled]));
@endphp

<div wire:ignore wire:key="{{ $uid }}-{{ $signature }}"
     x-data="searchSelect(@js($model))"
     class="rf-search-select {{ $invalid ? 'is-invalid' : '' }}">
    <select id="{{ $uid }}" x-ref="select" class="form-select" @disabled($disabled)>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected((string) $value === (string) $selected)>{{ $label }}</option>
        @endforeach
    </select>
</div>
