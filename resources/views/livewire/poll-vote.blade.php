<div class="relative min-h-[calc(100vh-3.5rem)]">
    {{-- Subtle branded top accent --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-[#0446DE]/[0.04] to-transparent dark:from-[#0446DE]/[0.06]"></div>

    <div class="relative mx-auto max-w-xl px-5 py-10 sm:py-16">
        {{-- Back link --}}
        <a href="{{ route('home') }}" wire:navigate class="group mb-8 inline-flex items-center gap-1.5 text-sm text-zinc-400 transition-colors hover:text-[#0446DE] dark:text-zinc-500 dark:hover:text-[#5B8AFF]">
            <flux:icon.arrow-left class="size-3.5 transition-transform group-hover:-translate-x-0.5" />
            {{ __('All Polls') }}
        </a>

        {{-- Poll Header --}}
        <div class="mb-8 space-y-4">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="inline-flex items-center rounded-full bg-[#0446DE]/10 px-2.5 py-0.5 text-xs font-medium text-[#0446DE] dark:bg-[#5B8AFF]/15 dark:text-[#5B8AFF]">
                    {{ $poll->status->label() }}
                </span>

                @if ($poll->expires_at)
                    <span class="inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
                        <flux:icon.clock class="size-3" />
                        @if ($poll->expires_at->isPast())
                            {{ __('Expired :time', ['time' => $poll->expires_at->diffForHumans()]) }}
                        @else
                            {{ __('Expires :time', ['time' => $poll->expires_at->diffForHumans()]) }}
                        @endif
                    </span>
                @endif
            </div>

            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-zinc-50">
                {{ $poll->title }}
            </h1>

            @if ($poll->description)
                <p class="text-[15px] leading-relaxed text-zinc-500 dark:text-zinc-400">
                    {{ $poll->description }}
                </p>
            @endif
        </div>

        {{-- Main Card --}}
        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm shadow-zinc-900/[0.03] dark:border-zinc-800 dark:bg-zinc-900 dark:shadow-none">
            {{-- Voting Form --}}
            <div class="p-5 sm:p-6">
                <p class="mb-5 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    {{ $hasVoted ? __('Change your vote by selecting a different option') : __('Select an option to cast your vote') }}
                </p>

                <div class="space-y-2.5" x-data="{ selected: @entangle('selectedOption') }">
                    @foreach ($options as $option)
                        <div
                            wire:key="option-{{ $option->id }}"
                            x-on:click="selected = {{ $option->id }}"
                            x-bind:class="selected === {{ $option->id }}
                                ? 'border-[#0446DE] bg-[#0446DE]/[0.04] ring-2 ring-[#0446DE]/30 dark:border-[#5B8AFF] dark:bg-[#5B8AFF]/[0.08] dark:ring-[#5B8AFF]/25'
                                : 'border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/60'"
                            class="group relative flex cursor-pointer items-center gap-3.5 overflow-hidden rounded-xl border px-4 py-3.5 transition-all duration-150"
                        >
                            @if ($hasVoted && $totalVotes > 0)
                                {{-- Progress bar background --}}
                                <div
                                    class="absolute inset-y-0 left-0 transition-all duration-700 ease-out
                                        {{ $option->isVotedOption
                                            ? 'bg-[#0446DE]/[0.08] dark:bg-[#5B8AFF]/[0.1]'
                                            : 'bg-zinc-100/70 dark:bg-zinc-800/50' }}"
                                    style="width: {{ $option->percentage }}%"
                                ></div>
                            @endif

                            <div class="relative z-10 flex w-full items-center gap-3.5">
                                {{-- Custom radio indicator --}}
                                <span
                                    x-bind:class="selected === {{ $option->id }}
                                        ? 'border-[#0446DE] dark:border-[#5B8AFF]'
                                        : 'border-zinc-300 group-hover:border-zinc-400 dark:border-zinc-600 dark:group-hover:border-zinc-500'"
                                    class="flex size-[18px] shrink-0 items-center justify-center rounded-full border-2 transition-all duration-150"
                                >
                                    <span
                                        x-show="selected === {{ $option->id }}"
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="scale-0 opacity-0"
                                        x-transition:enter-end="scale-100 opacity-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="scale-100 opacity-100"
                                        x-transition:leave-end="scale-0 opacity-0"
                                        class="size-2 rounded-full bg-[#0446DE] dark:bg-[#5B8AFF]"
                                    ></span>
                                </span>

                                <div class="flex flex-1 items-center justify-between">
                                    <span
                                        x-bind:class="selected === {{ $option->id }}
                                            ? 'text-[#0446DE] dark:text-[#5B8AFF]'
                                            : 'text-zinc-700 dark:text-zinc-200'"
                                        class="text-[15px] font-medium transition-colors duration-150"
                                    >
                                        {{ $option->option }}
                                    </span>

                                    <div class="flex items-center gap-2">
                                        @if ($hasVoted && $totalVotes > 0)
                                            <span class="text-sm font-semibold tabular-nums {{ $option->isVotedOption ? 'text-[#0446DE] dark:text-[#5B8AFF]' : 'text-zinc-600 dark:text-zinc-300' }}">
                                                {{ $option->percentage }}%
                                                @if ($option->votes_count > 0)
                                                    <span class="ml-0.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">({{ $option->votes_count }})</span>
                                                @endif
                                            </span>
                                        @endif

                                        @if ($option->isVotedOption)
                                            <span class="rounded-full bg-[#0446DE]/10 px-2 py-0.5 text-xs font-medium text-[#0446DE] dark:bg-[#5B8AFF]/15 dark:text-[#5B8AFF]">
                                                {{ __('Your vote') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('selectedOption')
                    <p class="mt-3 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Vote Button --}}
            <div class="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-800/30 sm:px-6">
                <button
                    wire:click="vote"
                    wire:loading.attr="disabled"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0446DE] px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-[#0339B8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0446DE] disabled:opacity-50 dark:bg-[#0446DE] dark:hover:bg-[#3366F0] dark:focus-visible:outline-[#5B8AFF] cursor-pointer"
                >
                    <span wire:loading.remove wire:target="vote">
                        {{ __('Vote') }}
                    </span>
                    <span wire:loading wire:target="vote" class="inline-flex items-center gap-2">
                        <flux:icon.loading class="size-4 animate-spin inline-flex items-center justify-center" />
                        {{ __('Voting...') }}
                    </span>
                </button>
            </div>
        </div>

        {{-- Poll creator --}}
        @if ($poll->user)
            <div class="mt-6 flex items-center gap-2 text-xs text-zinc-400 dark:text-zinc-600">
                <flux:icon.user class="size-3.5" />
                {{ __('Created by :name', ['name' => $poll->user->name]) }}
            </div>
        @endif
    </div>
</div>