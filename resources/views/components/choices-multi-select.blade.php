{{-- resources/views/components/choices-multi-select.blade.php --}}

@props([
'id' => '',
'wireModel' => '',
'options' => [], // Expects an associative array: ['value' => 'Label']
'selected' => [], // Expects an array of selected values (IDs)
'label' => '',
'help' => '',
'required' => false,
'class' => 'form-select' // Default Bootstrap class, adjust if using another framework
])

@php
$prettyname = $id ?: 'choices-' . Str::random(8); // Generate unique ID if not provided

// Directly check Livewire's error bag for the wireModel property
$allErrors = $errors->get($wireModel);
// Also get errors for specific array elements if they exist
foreach ($errors->keys() as $key) {
if (Str::startsWith($key, $wireModel . '.')) {
$allErrors = array_merge($allErrors, $errors->get($key));
}
}
$allErrors = array_unique($allErrors); // Remove duplicates
$hasError = !empty($allErrors);
@endphp

@if($label)
<label for="{{ $prettyname }}" class="form-label fw-medium text-dark">
    {{ $label }}
    @if($required)
    <span class="text-danger">*</span>
    @endif
</label>
@endif

{{--
    x-data="choicesMultiSelect(...)" connects this element to the Alpine.js data component defined in app.js
    wire:ignore prevents Livewire from re-rendering this element, letting Alpine/Choices manage it.
--}}
<div wire:ignore x-data="choicesMultiSelect('{{ $prettyname }}', '{{ $wireModel }}', {{ json_encode((array)$selected) }})">
    <select
        id="{{ $prettyname }}"
        x-ref="{{ $prettyname }}" {{-- x-ref allows Alpine to get a direct DOM reference --}}
        multiple {{-- Important for multi-select dropdown --}}
        class="{{ $class }} @if($hasError) is-invalid @endif" {{-- Apply invalid class if errors exist --}}>
        @if(count($options) > 0)
        @foreach($options as $value => $optionLabel)
        <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
        @endif
    </select>
</div>

@if($help)
<div class="form-text">{{ $help }}</div>
@endif

{{-- Display all errors related to this wireModel --}}
@if($hasError)
@foreach($allErrors as $err)
<div class="text-danger small mt-1">{{ $err }}</div>
@endforeach
@endif