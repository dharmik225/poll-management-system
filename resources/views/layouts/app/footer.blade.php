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