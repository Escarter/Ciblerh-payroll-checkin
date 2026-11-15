@props([
    'code' => null,
    'title' => null,
    'subtitle' => null,
    'badge' => null,
    'back' => true,
    'backUrl' => null,
    'backLabel' => null,
])

<x-layouts.app>
    <main wire:ignore>
        <x-auth-shell
            :code="$code"
            :badge="$badge"
            :title="$title ?? ($code ? 'Error ' . $code : __('app.ciblerh'))"
            :subtitle="$subtitle"
            :back="$back"
            :back-url="$backUrl"
            :back-label="$backLabel"
        >
            {{ $slot }}
        </x-auth-shell>
    </main>
</x-layouts.app>
