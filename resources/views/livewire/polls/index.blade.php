<section class="w-full space-y-6">
    <div class="space-y-6 max-w-full">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="lg">{{ __('Polls') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Create, edit, and manage polls.') }}</flux:text>
            </div>

            <flux:button variant="primary" wire:click="openCreateForm">
                {{ __('New Poll') }}
            </flux:button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                :label="__('Search')"
                :placeholder="__('Search by title or description')"
            />

            <flux:select wire:model.live="statusFilter" :label="__('Status')">
                <option value="all">{{ __('All statuses') }}</option>
                @foreach (\App\Enums\PollStatus::cases() as $pollStatus)
                    <option value="{{ $pollStatus->value }}">{{ $pollStatus->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="overflow-x-auto">
            <div class="min-w-[700px] max-w-full mx-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Title') }}</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Status') }}</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Options') }}</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Votes') }}</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Expires') }}</th>
                            <th class="px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($polls as $poll)
                            <tr class="text-zinc-700 dark:text-zinc-200">
                                <td class="px-4 py-3 max-w-[250px] whitespace-pre-line break-words">
                                    <div class="font-medium">{{ $poll->title }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <flux:badge :color="$poll->status->color()">{{ $poll->status->label() }}</flux:badge>
                                </td>
                                <td class="px-4 py-3">{{ $poll->options_count }}</td>
                                <td class="px-4 py-3">{{ $poll->votes_count }}</td>
                                <td class="px-4 py-3">
                                    {{ $poll->expires_at?->format('M j, Y g:i A') ?? __('No expiry') }}
                                </td>
                                <td class="px-4 py-3">
                                    <flux:dropdown >
                                        <flux:button icon:trailing="chevron-down" size="sm" variant="primary" color="zinc">{{ __('Actions') }}</flux:button>
                                        <flux:menu>
                                            <flux:menu.item
                                                icon="eye"
                                                size="sm"
                                                :href="route('polls.show', $poll)"
                                                wire:navigate
                                            >
                                                {{ __('View') }}
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="share"
                                                size="sm"
                                                :disabled="!$poll->status->canReceiveResponses()"
                                                wire:click="sharePoll({{ $poll->id }})"
                                            >
                                                {{ __('Share') }}
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="pencil"
                                                size="sm"
                                                :disabled="$poll->votes_count > 0"
                                                wire:click="openEditForm({{ $poll->id }})"
                                            >
                                                {{ __('Edit') }}
                                            </flux:menu.item>
                                            <flux:menu.item
                                                icon="trash"
                                                variant="danger"
                                                size="sm"
                                                wire:click="confirmDelete({{ $poll->id }})"
                                            >
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No polls found. Create the first poll to get started.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            {{ $polls->links() }}
        </div>
    </div>

    <flux:modal wire:model="showForm" class="!max-w-6xl">
        <div class="space-y-6 max-w-4xl w-full">
            <div>
            <flux:heading size="lg">
                    {{ $editingPollId ? __('Edit Poll') : __('Create Poll') }}
                </flux:heading>
                <flux:text class="mt-1">{{ __('Define the question and available options.') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <flux:input wire:model="title" :label="__('Title')" required />
                </div>

                <div class="md:col-span-2">
                    <flux:textarea
                        wire:model="description"
                        :label="__('Description')"
                        rows="3"
                    />
                </div>

                <flux:select wire:model="status" :label="__('Status')">
                    @foreach (\App\Enums\PollStatus::cases() as $pollStatus)
                        <option value="{{ $pollStatus->value }}">{{ $pollStatus->label() }}</option>
                    @endforeach
                </flux:select>

                <div>
                    <flux:input
                        wire:model="expiresAt"
                        type="datetime-local"
                        :label="__('Expires at')"
                    />
                </div>
            </div>

            <div class="space-y-3">
                <flux:heading size="sm">{{ __('Options') }}</flux:heading>

                <div class="grid gap-3">
                    @foreach ($options as $index => $option)
                        <div class="flex flex-col gap-2 md:flex-row md:items-end" wire:key="option-{{ $index }}">
                            <div class="flex-1">
                                <flux:input
                                    wire:model="options.{{ $index }}"
                                    :label="__('Option :number', ['number' => $index + 1])"
                                />
                            </div>
                            <div class="md:ml-3 flex items-center justify-end">
                                <flux:button
                                    variant="danger"
                                    icon="trash"
                                    wire:click="removeOption({{ $index }})"
                                >
                                    {{ __('Remove') }}
                                </flux:button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-center">
                    <flux:button
                        variant="filled"
                        icon="plus"
                        wire:click="addOption"
                    >
                        {{ __('Add option') }}
                    </flux:button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelForm">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" color="indigo" wire:click="save" wire:loading.attr="disabled">
                    {{ $editingPollId ? __('Update poll') : __('Create poll') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showDelete" class="!max-w-xl">
        <div class="space-y-6 max-w-xl w-full">
            <div>
                <flux:heading size="lg">{{ __('Delete Poll') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('This action cannot be undone. All related options and votes will be removed.') }}
                </flux:text>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelDelete">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="delete">
                    {{ __('Delete poll') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showShare" class="!max-w-xl">
        <div
            class="space-y-6 max-w-xl w-full"
            x-data="{
                copied: false,
                copyLink() {
                    const input = this.$refs.shareInput;
                    const text = input.value;
                    const copyText = async () => {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            input.select();
                            document.execCommand('copy');
                            window.getSelection().removeAllRanges();
                        }
                    };
                    copyText()
                        .then(() => { this.copied = true; })
                        .catch(() => { this.copied = false; })
                        .finally(() => {
                            setTimeout(() => { this.copied = false; }, 2000);
                        });
                }
            }"
        >
            <div>
                <flux:heading size="lg">{{ __('Share Poll') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Copy the link below to share this poll with others.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-2">
                <flux:input
                    x-ref="shareInput"
                    :value="$shareUrl"
                    readonly
                    class="flex-1"
                />
                <flux:button
                    variant="primary"
                    icon="clipboard"
                    x-on:click="copyLink"
                >
                    <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy') }}'"></span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
