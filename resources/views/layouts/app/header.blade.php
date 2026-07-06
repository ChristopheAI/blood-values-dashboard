<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="heart" :href="route('blood-results.overview')" :current="request()->routeIs('blood-results.*')" wire:navigate>
                    {{ __('Mijn bloedwaarden') }}
                </flux:navbar.item>
                <flux:navbar.item icon="folder-git-2" :href="route('blood-tests.index')" :current="request()->routeIs('blood-tests.*')" wire:navigate>
                    {{ __('Bloedtesten') }}
                </flux:navbar.item>
                <flux:navbar.item icon="book-open-text" :href="route('consult-overview.index')" :current="request()->routeIs('consult-overview.*')" wire:navigate>
                    {{ __('Consultlijst') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Overzicht')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="heart" :href="route('blood-results.overview')" :current="request()->routeIs('blood-results.*')" wire:navigate>
                        {{ __('Mijn bloedwaarden') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="folder-git-2" :href="route('blood-tests.index')" :current="request()->routeIs('blood-tests.*')" wire:navigate>
                        {{ __('Bloedtesten') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open-text" :href="route('consult-overview.index')" :current="request()->routeIs('consult-overview.*')" wire:navigate>
                        {{ __('Consultlijst') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
