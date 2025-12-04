<x-layouts.app>
    <main class='content pb-4'>
        {{$slot}}
    </main>
    
    <!-- Global Search Component -->
    @livewire('components.global-search')
</x-layouts.app>