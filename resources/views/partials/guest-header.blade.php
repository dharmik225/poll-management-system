{{-- Guest Navigation Header --}}
<header class="fixed top-0 z-50 w-full border-b border-zinc-200/80 bg-white/90 backdrop-blur-xl dark:border-zinc-800 dark:bg-zinc-950/90">
    <div class="mx-auto flex h-14 max-w-3xl items-center justify-between px-5">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 transition-opacity hover:opacity-80" wire:navigate>
            <div class="flex size-7 items-center justify-center rounded-lg bg-[#0446DE]">
                <x-app-logo-icon class="size-4 fill-current text-white" />
            </div>
            <span class="text-[15px] font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ config('app.name', 'Poll Management') }}
            </span>
        </a>

        <nav class="flex items-center gap-1.5">
            @auth
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" icon-trailing="chevron-down" size="sm" class="text-sm text-zinc-600 dark:text-zinc-300">
                        {{ auth()->user()->name }}
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>
                            {{ __('Profile') }}
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
                <flux:button :href="route('login')" variant="ghost" size="sm" class="text-sm text-zinc-600 dark:text-zinc-300">
                    {{ __('Log in') }}
                </flux:button>
                @if (Route::has('register'))
                    <flux:button :href="route('register')" size="sm" class="!bg-[#0446DE] !text-white hover:!bg-[#0339B8]">
                        {{ __('Sign up') }}
                    </flux:button>
                @endif
            @endauth
        </nav>
    </div>
</header>
