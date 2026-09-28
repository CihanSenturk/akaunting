<x-layouts.admin>
    <x-slot name="title">
        {{ trans('accounts.bank_feed.title') }}
    </x-slot>

    <x-slot name="favorite"
        title="{{ trans('general.title.new', ['type' => trans_choice('general.accounts', 1)]) }}"
        icon="account_balance"
        route="accounts.connect"
    ></x-slot>

    <x-slot name="content">
        {{-- The same page every other list shows when it has nothing yet: the two ways of
             adding an account are offered one under the other, the way creating and importing
             are offered elsewhere --}}
        <x-empty-page
            page="accounts"
            group="banking"
            image="{{ asset('public/img/empty_pages/transactions.png') }}"
            title="{{ trans('accounts.bank_feed.title') }}"
            description="{{ trans('accounts.bank_feed.description') }}"
            :buttons="[
                [
                    'url' => route('apps.app.show', 'bank-feeds'),
                    'permission' => 'read-modules-home',
                    'text' => trans('accounts.bank_feed.action'),
                    'description' => trans('accounts.bank_feed.action_description'),
                    'active_badge' => true,
                ],
                [
                    'url' => route('accounts.create'),
                    'permission' => 'create-banking-accounts',
                    'text' => trans('accounts.bank_feed.add_without'),
                    'description' => trans('accounts.bank_feed.add_without_description'),
                    'active_badge' => false,
                ],
            ]"
        />
    </x-slot>
</x-layouts.admin>
