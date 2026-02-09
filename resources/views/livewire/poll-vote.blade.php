<?php
/** @var \App\Models\Poll $poll */
/** @var \Illuminate\Database\Eloquent\Collection<\App\Models\PollOption> $options */
/** @var int $totalVotes */
?>

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
            @if (! $hasVoted)
                {{-- Voting Form --}}
                <div class="p-5 sm:p-6">
                    <p class="mb-5 text-sm font-medium text-zinc-500 dark:text-zinc-400">
                        {{ __('Select an option to cast your vote') }}
                    </p>

                    {{-- Alpine handles instant visual selection, @entangle syncs value to Livewire --}}
                    <div class="space-y-2.5" x-data="{ selected: @entangle('selectedOption') }">
                        @foreach ($options as $option)
                            <div
                                wire:key="option-{{ $option->id }}"
                                x-on:click="selected = {{ $option->id }}"
                                x-bind:class="selected === {{ $option->id }}
                                    ? 'border-[#0446DE] bg-[#0446DE]/[0.04] ring-2 ring-[#0446DE]/30 dark:border-[#5B8AFF] dark:bg-[#5B8AFF]/[0.08] dark:ring-[#5B8AFF]/25'
                                    : 'border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/60'"
                                class="group flex cursor-pointer items-center gap-3.5 rounded-xl border px-4 py-3.5 transition-all duration-150"
                            >
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

                                <span
                                    x-bind:class="selected === {{ $option->id }}
                                        ? 'text-[#0446DE] dark:text-[#5B8AFF]'
                                        : 'text-zinc-700 dark:text-zinc-200'"
                                    class="text-[15px] font-medium transition-colors duration-150"
                                >
                                    {{ $option->option }}
                                </span>
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
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#0446DE] px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-[#0339B8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0446DE] disabled:opacity-50 dark:bg-[#0446DE] dark:hover:bg-[#3366F0] dark:focus-visible:outline-[#5B8AFF]"
                    >
                        <span wire:loading.remove wire:target="vote">{{ __('Submit Vote') }}</span>
                        <span wire:loading wire:target="vote" class="flex items-center gap-2">
                            <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            {{ __('Submitting...') }}
                        </span>
                    </button>
                </div>
            @else
                {{-- Results View --}}
                <div class="p-5 sm:p-6">
                    <div class="mb-5 flex items-center justify-between">
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Results') }}</p>
                        <span class="rounded-full bg-[#0446DE]/10 px-2.5 py-0.5 text-xs font-semibold tabular-nums text-[#0446DE] dark:bg-[#5B8AFF]/15 dark:text-[#5B8AFF]">
                            {{ trans_choice(':count vote|:count votes', $totalVotes) }}
                        </span>
                    </div>

                    <div class="space-y-3">
                        @foreach ($options as $option)
                            @php
                                $percentage = $totalVotes > 0
                                    ? round(($option->votes_count / $totalVotes) * 100, 1)
                                    : 0;
                                $isVotedOption = $votedOptionId === $option->id;
                                $isLeading = $option->votes_count === $options->max('votes_count') && $option->votes_count > 0;
                            @endphp

                            <div wire:key="result-{{ $option->id }}" class="relative overflow-hidden rounded-xl border transition-all duration-150
                                {{ $isVotedOption
                                    ? 'border-[#0446DE]/30 bg-[#0446DE]/[0.03] dark:border-[#5B8AFF]/25 dark:bg-[#5B8AFF]/[0.05]'
                                    : 'border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900' }}"
                            >
                                {{-- Progress bar background --}}
                                <div
                                    class="absolute inset-y-0 left-0 transition-all duration-700 ease-out
                                        {{ $isVotedOption
                                            ? 'bg-[#0446DE]/[0.08] dark:bg-[#5B8AFF]/[0.1]'
                                            : ($isLeading ? 'bg-[#0446DE]/[0.05] dark:bg-[#5B8AFF]/[0.06]' : 'bg-zinc-100/70 dark:bg-zinc-800/50') }}"
                                    style="width: {{ $percentage }}%"
                                ></div>

                                <div class="relative flex items-center justify-between px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if ($isVotedOption)
                                            <span class="flex size-5 items-center justify-center rounded-full bg-[#0446DE] dark:bg-[#5B8AFF]">
                                                <flux:icon.check class="size-3 text-white" />
                                            </span>
                                        @endif

                                        <span class="text-[15px] font-medium {{ $isVotedOption ? 'text-[#0446DE] dark:text-[#5B8AFF]' : 'text-zinc-700 dark:text-zinc-200' }}">
                                            {{ $option->option }}
                                        </span>

                                        @if ($isLeading)
                                            <span class="rounded-md bg-[#0446DE]/10 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#0446DE] dark:bg-[#5B8AFF]/15 dark:text-[#5B8AFF]">
                                                {{ __('Top') }}
                                            </span>
                                        @endif
                                    </div>

                                    <span class="text-sm font-semibold tabular-nums {{ $isVotedOption ? 'text-[#0446DE] dark:text-[#5B8AFF]' : ($isLeading ? 'text-[#0446DE]/80 dark:text-[#5B8AFF]/80' : 'text-zinc-600 dark:text-zinc-300') }}">
                                        {{ $percentage }}%
                                        <span class="ml-0.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">({{ $option->votes_count }})</span>
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-zinc-100 bg-zinc-50/50 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-800/30 sm:px-6">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">
                            {{ __('Your vote:') }}
                            <span class="font-medium text-[#0446DE] dark:text-[#5B8AFF]">
                                {{ $options->firstWhere('id', $votedOptionId)?->option }}
                            </span>
                        </p>

                        <a href="{{ route('home') }}" wire:navigate class="text-sm font-medium text-zinc-500 transition-colors hover:text-[#0446DE] dark:text-zinc-400 dark:hover:text-[#5B8AFF]">
                            {{ __('Back') }}
                        </a>
                    </div>
                </div>
            @endif
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
