<section class="w-full space-y-6">
    <div class="space-y-6 max-w-full">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" icon="arrow-left" :href="route('polls.index')" wire:navigate />
                <div>
                    <div class="flex items-center gap-2">
                        <flux:heading size="lg">{{ $poll->title }}</flux:heading>
                        <flux:badge :color="$poll->status->color()">{{ $poll->status->label() }}</flux:badge>
                    </div>
                    @if ($poll->description)
                        <flux:text class="mt-1">{{ $poll->description }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2">
                <flux:button variant="ghost" icon="arrow-path" wire:click="$refresh" size="sm">
                    {{ __('Refresh') }}
                </flux:button>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                        <flux:icon name="hand-raised" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:text class="text-xs uppercase tracking-wide">{{ __('Total Votes') }}</flux:text>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalVotes) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                        <flux:icon name="list-bullet" class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <flux:text class="text-xs uppercase tracking-wide">{{ __('Options') }}</flux:text>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $options->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                        <flux:icon name="trophy" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                    <div>
                        <flux:text class="text-xs uppercase tracking-wide">{{ __('Leading') }}</flux:text>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 truncate max-w-[140px]" title="{{ $leadingOption?->option ?? __('N/A') }}">
                            {{ $leadingOption?->option ?? __('N/A') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/30">
                        <flux:icon name="clock" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <flux:text class="text-xs uppercase tracking-wide">{{ __('Expires') }}</flux:text>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $poll->expires_at?->format('M j, Y') ?? __('No expiry') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Poll Info --}}
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="sm" class="mb-3">{{ __('Poll Details') }}</flux:heading>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $poll->created_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Last Updated') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $poll->updated_at->format('M j, Y g:i A') }}</dd>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd><flux:badge :color="$poll->status->color()" size="sm">{{ $poll->status->label() }}</flux:badge></dd>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Expires') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ $poll->expires_at?->format('M j, Y g:i A') ?? __('No expiry') }}
                        </dd>
                    </div>
                    @if ($poll->expires_at)
                        <flux:separator />
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Time Left') }}</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                                @if ($poll->expires_at->isPast())
                                    <flux:badge color="red" size="sm">{{ __('Expired') }}</flux:badge>
                                @else
                                    {{ $poll->expires_at->diffForHumans() }}
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            {{-- Vote Distribution --}}
            <div class="lg:col-span-2 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="sm">{{ __('Vote Distribution') }}</flux:heading>
                    <flux:text class="text-xs">{{ __(':count total votes', ['count' => number_format($totalVotes)]) }}</flux:text>
                </div>

                @if ($totalVotes > 0)
                    <div class="space-y-4">
                        @foreach ($optionStats as $stat)
                            <div wire:key="stat-{{ $stat->id }}">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200 truncate max-w-[60%]">
                                        {{ $stat->option }}
                                    </span>
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2">
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($stat->votes_count) }}</span>
                                        <span>({{ $stat->percentage }}%)</span>
                                    </span>
                                </div>
                                <div class="h-3 w-full rounded-full bg-zinc-100 dark:bg-zinc-800 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500 ease-out {{ $leadingOption && $stat->id === $leadingOption->id ? 'bg-blue-600 dark:bg-blue-500' : 'bg-zinc-400 dark:bg-zinc-600' }}"
                                        style="width: {{ $stat->percentage }}%"
                                    ></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-zinc-400 dark:text-zinc-500">
                        <flux:icon name="chart-bar" class="h-12 w-12 mb-2" />
                        <flux:text>{{ __('No votes yet.') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        {{-- Voters Table --}}
        <div class="space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <flux:heading size="sm">{{ __('Voters') }}</flux:heading>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <flux:input
                    wire:model.live="voterSearch"
                    type="search"
                    :label="__('Search voters')"
                    :placeholder="__('Search by name, email, or IP')"
                />

                <flux:select wire:model.live="optionFilter" :label="__('Filter by option')">
                    <option value="all">{{ __('All options') }}</option>
                    @foreach ($options as $option)
                        <option value="{{ $option->id }}">{{ $option->option }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="overflow-x-auto">
                <div class="min-w-[700px] max-w-full mx-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Voter') }}</th>
                                <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Chosen Option') }}</th>
                                <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('IP Address') }}</th>
                                <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Voted At') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($voters as $vote)
                                <tr wire:key="vote-{{ $vote->id }}" class="text-zinc-700 dark:text-zinc-200">
                                    <td class="px-4 py-3">
                                        @if ($vote->user)
                                            <div class="flex items-center gap-2">
                                                <flux:avatar :name="$vote->user->name" :initials="$vote->user->initials()" size="xs" />
                                                <div>
                                                    <div class="font-medium">{{ $vote->user->name }}</div>
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $vote->user->email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center gap-2">
                                                <flux:avatar name="Anonymous" initials="A" size="xs" />
                                                <span class="text-zinc-400 italic">{{ __('Anonymous') }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <flux:badge color="zinc" size="sm">{{ $vote->pollOption?->option ?? __('Deleted option') }}</flux:badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ $vote->ip_address ?? __('N/A') }}</code>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                        <div>{{ $vote->created_at->format('M j, Y') }}</div>
                                        <div class="text-xs">{{ $vote->created_at->format('g:i A') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        @if ($voterSearch || $optionFilter !== 'all')
                                            {{ __('No voters match the current filters.') }}
                                        @else
                                            {{ __('No votes have been cast yet.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                {{ $voters->links() }}
            </div>
        </div>
    </div>
</section>
