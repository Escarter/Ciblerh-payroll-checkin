{{--
    Shared person avatar (SchoolOS pattern).
    Renders a profile photo when one is available, falling back to initials underneath
    if none is set or if the image fails to load (`onerror="this.remove()"`).
--}}
@props([
    'user' => null,
    'name' => null,
    'initials' => null,
    'src' => null,
    'size' => 'md',
    'class' => 'avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3',
    'textClass' => 'text-white',
])
@php
    $resolvedName = $name ?? ($user?->name ?? '');
    $resolvedInitials = $initials ?? ($user?->initials ?: (function_exists('initials') && $resolvedName ? initials($resolvedName) : '—'));
    $pixels = ['xs' => 24, 'sm' => 32, 'md' => 44, 'lg' => 52, 'xl' => 72][$size] ?? 44;
    $resolvedSrc = $src ?? ($user && method_exists($user, 'photoUrl') ? $user->photoUrl($pixels <= 48) : null);
@endphp
<div {{ $attributes->merge(['class' => $class . ' position-relative overflow-hidden flex-shrink-0']) }}>
    <span class="{{ $textClass }}">{{ $resolvedInitials }}</span>
    @if ($resolvedSrc)
        <img
            src="{{ $resolvedSrc }}"
            alt="{{ $resolvedName }}"
            width="{{ $pixels }}"
            height="{{ $pixels }}"
            loading="lazy"
            decoding="async"
            onerror="this.remove()"
            class="rf-avatar-img position-absolute top-0 start-0 w-100 h-100"
        />
    @endif
</div>
