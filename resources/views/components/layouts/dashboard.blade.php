<x-layouts.app>
    <x-layouts.navigation.topbar />
    <x-layouts.navigation.sidebar />
    <main class='content pb-4'>
        <x-layouts.navigation.navbar />
        {{$slot}}
    </main>
    
    <!-- Global Search Component -->
    @livewire('components.global-search')
</x-layouts.app>