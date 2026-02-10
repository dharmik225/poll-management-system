<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        @include('partials.guest-header')

        <main class="pt-14">
            {{ $slot }}
        </main>

        @include('partials.guest-footer')

        @fluxScripts
    </body>
</html>
