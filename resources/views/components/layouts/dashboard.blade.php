<x-layouts.app>
    <x-layouts.navigation.topbar />
    <x-layouts.navigation.sidebar />
    <main class='content pb-4'>
        <x-layouts.navigation.navbar />
        {{$slot}}
    </main>
</x-layouts.app>