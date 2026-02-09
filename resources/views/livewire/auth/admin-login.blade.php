<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Admin Login')" :description="__('Enter your admin credentials below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <input type="hidden" name="admin_login" value="1">

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an admin account?') }}</span>
            <flux:link :href="route('admin.register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Not an admin?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('User login') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
