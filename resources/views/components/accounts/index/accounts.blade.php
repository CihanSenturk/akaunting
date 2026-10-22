<div class="flex flex-col gap-8 mt-6">
    @stack('account_groups_start')

    @foreach ($getGroupedAccounts() as $group => $group_accounts)
        <section class="flex flex-col gap-4" id="index-account-group-{{ $group }}">
            <div class="flex items-baseline justify-between">
                <h2 class="text-sm font-bold text-purple uppercase tracking-wide">
                    {{ $getGroupTitle($group) }}
                </h2>

                <span class="text-sm text-black-400">
                    {{ count($group_accounts) }}
                </span>
            </div>

            @stack('account_group_' . $group . '_start')

            @foreach ($group_accounts as $item)
                <x-accounts.index.account
                    :account="$item"
                    :overview="$overview[$item->id] ?? []"
                />
            @endforeach

            @stack('account_group_' . $group . '_end')
        </section>
    @endforeach

    @stack('account_groups_end')
</div>
