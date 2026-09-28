<x-layouts.admin>
    <x-slot name="title">
        {{ trans_choice('general.accounts', 2) }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans_choice('general.accounts', 2) }}"
        icon="account_balance"
        route="accounts.index"
    ></x-slot>

    <x-slot name="buttons">
        @can('create-banking-accounts')
            {{-- Where a new account begins: an app that connects banks takes that page over,
                 and without one it offers the app beside the way on to the form --}}
            <x-link href="{{ route('accounts.connect') }}" kind="primary" id="index-more-actions-new-account">
                {{ trans('general.title.new', ['type' => trans_choice('general.accounts', 1)]) }}
            </x-link>
        @endcan
    </x-slot>

    <x-slot name="content">
        <x-index.container>
            <x-index.search
                search-string="App\Models\Banking\Account"
                bulk-action="App\BulkActions\Banking\Accounts"
            />

            <x-index.cards
                sort-id="index-sort-account"
                :sortable="[
                    'name'      => trans('general.name'),
                    'number'    => trans('accounts.number'),
                    'bank_name' => trans('accounts.bank_name'),
                    'balance'   => trans('accounts.current_balance'),
                ]"
            >
                @foreach($accounts as $item)
                    <x-index.card :model="$item" :href="route('accounts.show', $item->id)">
                        <x-slot name="title">
                            <span class="truncate">{{ $item->name }}</span>

                            @if (! $item->enabled)
                                <x-index.disable text="{{ trans_choice('general.accounts', 1) }}" />
                            @endif

                            @if (setting('default.account') == $item->id)
                                <x-index.default text="{{ trans('accounts.default_account') }}" />
                            @endif
                        </x-slot>

                        <x-slot name="subtitle">
                            @if (! empty($item->number))
                                {{ $item->number }}
                            @else
                                <x-empty-data />
                            @endif
                        </x-slot>

                        <x-slot name="actions">
                            <x-index.card.actions :model="$item" id="index-line-actions-account-{{ $item->id }}" />
                        </x-slot>

                        <div class="min-w-0 text-xs text-black-400">
                            <div class="truncate">
                                @if (! empty($item->bank_name))
                                    {{ $item->bank_name }}
                                @else
                                    <x-empty-data />
                                @endif
                            </div>

                            <div class="truncate">
                                @if (! empty($item->bank_phone))
                                    {{ $item->bank_phone }}
                                @else
                                    <x-empty-data />
                                @endif
                            </div>
                        </div>

                        <div class="shrink-0 ltr:ml-2 rtl:mr-2 ltr:text-right rtl:text-left">
                            <div class="text-xs text-black-400">
                                {{ trans('accounts.current_balance') }}
                            </div>

                            <div class="text-lg font-bold">
                                <x-money :amount="$item->balance" :currency="$item->currency_code" />
                            </div>
                        </div>
                    </x-index.card>
                @endforeach
            </x-index.cards>

            <x-pagination :items="$accounts" />
        </x-index.container>
    </x-slot>

    <x-script folder="banking" file="accounts" />
</x-layouts.admin>
