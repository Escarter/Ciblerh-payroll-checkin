@props(['class' => '', 'id' => null, 'textClass' => 'rf-brand-text'])
@if (\App\Support\Branding::get('show_logo', true))
    <img src="{{ \App\Support\Branding::get('logo') }}" @if($id) id="{{ $id }}" @endif class="{{ $class }}" alt="{{ \App\Support\Branding::get('app_name') }}">
@else
    <span @if($id) id="{{ $id }}" @endif class="{{ $textClass }}">{{ \App\Support\Branding::get('app_name') }}</span>
@endif
