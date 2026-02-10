<footer class="border-t border-zinc-100 dark:border-zinc-800/60">
    <div class="mx-auto flex max-w-3xl items-center justify-between px-5 py-5">
        <p class="text-xs text-zinc-400 dark:text-zinc-500">
            &copy; {{ date('Y') }} {{ config('app.name', 'Poll Management') }}
        </p>
        <div class="flex items-center gap-4">
            @guest
                <a href="{{ route('admin.login') }}" class="text-xs text-zinc-400 transition-colors hover:text-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300">
                    {{ __('Admin') }}
                </a>
            @endguest
        </div>
    </div>
</footer>
