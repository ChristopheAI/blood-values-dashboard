<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('Bloedwaarden') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-neutral-50 text-neutral-900 dark:bg-neutral-950 dark:text-white">
        <main class="mx-auto flex min-h-screen w-full max-w-4xl flex-col justify-center gap-8 px-6 py-12">
            <section class="space-y-4">
                <p class="text-sm font-medium uppercase tracking-wide text-neutral-500 dark:text-neutral-400">{{ __('Lokale opvolging') }}</p>
                <h1 class="text-3xl font-semibold tracking-normal">{{ __('Bloedwaarden') }}</h1>
                <p class="max-w-2xl text-base text-neutral-700 dark:text-neutral-300">
                    {{ __('Upload lab-PDFs lokaal, review waarden, en maak een compacte consultlijst uit bevestigde gegevens.') }}
                </p>
            </section>

            @if (Route::has('login'))
                <nav class="flex flex-wrap gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex rounded-md bg-neutral-900 px-4 py-2 font-medium text-white dark:bg-white dark:text-neutral-900">
                            {{ __('Dashboard openen') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex rounded-md bg-neutral-900 px-4 py-2 font-medium text-white dark:bg-white dark:text-neutral-900">
                            {{ __('Inloggen') }}
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex rounded-md border border-neutral-300 px-4 py-2 font-medium text-neutral-800 dark:border-neutral-700 dark:text-neutral-100">
                                {{ __('Registreren') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </main>
    </body>
</html>
