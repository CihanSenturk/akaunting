<x-index.container>
    @if ((! $hideSearchString) && (! $hideBulkAction))
        <x-index.search
            search-string="{{ $searchStringModel }}"
            bulk-action="{{ $bulkActionClass }}"
        />
    @elseif ((! $hideSearchString) && $hideBulkAction)
        <x-index.search
            search-string="{{ $searchStringModel }}"
        />
    @elseif ($hideSearchString && (! $hideBulkAction))
        <x-index.search
            bulk-action="{{ $bulkActionClass }}"
        />
    @endif

    <x-accounts.index.accounts :accounts="$accounts" />

    @if (! $hidePagination)
        <x-pagination :items="$accounts" />
    @endif
</x-index.container>
