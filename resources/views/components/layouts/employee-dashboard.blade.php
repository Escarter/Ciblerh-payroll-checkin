<x-layouts.app>
    <x-navigation.topbar />
    <x-navigation.employee-sidebar />
    <main class='content pb-4'>
        <x-navigation.navbar />
        {{$slot}}
    </main>
</x-layouts.app>
