<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
        {{-- Navigation --}}
        <header class="fixed top-0 z-50 w-full border-b border-zinc-200 bg-white/80 backdrop-blur-md dark:border-zinc-700 dark:bg-zinc-900/80">
            <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-6">
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-md bg-zinc-900 dark:bg-zinc-100">
                        <x-app-logo-icon class="size-5 fill-current text-white dark:text-zinc-900" />
                    </div>
                    <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ config('app.name', 'Poll Management') }}
                    </span>
                </div>

                <nav class="flex items-center gap-2">
                    @auth
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" icon-trailing="chevron-down" class="text-sm">
                                {{ auth()->user()->name }}
                            </flux:button>

                            <flux:menu>
                                <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>
                                    {{ __('Profile') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('appearance.edit')" icon="paint-brush" wire:navigate>
                                    {{ __('Appearance') }}
                                </flux:menu.item>
                                <flux:menu.item :href="route('user-password.edit')" icon="lock-closed" wire:navigate>
                                    {{ __('Password') }}
                                </flux:menu.item>

                                @if(auth()->user()->isAdmin())
                                    <flux:menu.separator />
                                    <flux:menu.item :href="route('dashboard')" icon="squares-2x2" wire:navigate>
                                        {{ __('Admin Dashboard') }}
                                    </flux:menu.item>
                                @endif

                                <flux:menu.separator />

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                        {{ __('Log Out') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    @else
                        <flux:button :href="route('login')" variant="ghost" size="sm">
                            {{ __('Log in') }}
                        </flux:button>
                        @if (Route::has('register'))
                            <flux:button :href="route('register')" variant="primary" size="sm">
                                {{ __('Sign up') }}
                            </flux:button>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        {{-- Hero Section --}}
        <main class="pt-16">
            <section class="relative overflow-hidden">
                <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-5xl flex-col items-center justify-center px-6 py-24 text-center">
                    <flux:badge variant="pill" color="zinc" class="mb-6">
                        {{ __('Open Source Poll Platform') }}
                    </flux:badge>

                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight bg-gradient-to-r from-[#304ffe] to-[#6200ea] bg-clip-text text-transparent sm:text-5xl">
                        {{ __('Create, share, and collect responses with ease') }}
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg text-zinc-600 dark:text-zinc-400">
                        {{ __('Build beautiful polls in seconds. Share them with a link and watch the results come in real time. Simple, fast, and free.') }}
                    </p>

                    <div class="mt-10 flex items-center gap-4">
                        @auth
                            @if(auth()->user()->isAdmin())
                                <flux:button :href="route('polls.index')" variant="primary" icon-trailing="arrow-right">
                                    {{ __('Manage Polls') }}
                                </flux:button>
                            @endif
                        @else
                            <flux:button :href="route('register')" variant="primary" color="indigo">
                                {{ __('Get Started') }}
                            </flux:button>
                            <flux:button :href="route('login')" variant="ghost" color="zinc">
                                {{ __('Log in') }}
                            </flux:button>
                        @endauth
                    </div>
                </div>
            </section>

            {{-- Features Section --}}
            <section class="border-t border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mx-auto max-w-5xl px-6 py-24">
                    <div class="text-center">
                        <h2 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ __('Everything you need to run polls') }}
                        </h2>
                        <p class="mt-3 text-zinc-600 dark:text-zinc-400">
                            {{ __('A simple yet powerful polling system with all the essentials.') }}
                        </p>
                    </div>

                    <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.bolt class="size-5 text-zinc-600 dark:text-zinc-300" />
                            </div>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Quick Setup') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Create a poll with multiple options in under a minute. No complex configuration needed.') }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.link class="size-5 text-zinc-600 dark:text-zinc-300" />
                            </div>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Shareable Links') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Share your poll with a simple link. Anyone can vote without needing an account.') }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex size-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon.chart-bar class="size-5 text-zinc-600 dark:text-zinc-300" />
                            </div>
                            <h3 class="mt-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Live Results') }}</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Watch responses come in real time. Track participation and analyze results instantly.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-zinc-200 dark:border-zinc-700">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-6">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Poll Management') }}
                </p>
                <div class="flex items-center gap-4">
                    @guest
                        <flux:link :href="route('admin.login')" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                            {{ __('Admin') }}
                        </flux:link>
                    @endguest
                </div>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
