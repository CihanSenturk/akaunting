<?php

namespace App\View\Components\Accounts\Index;

use App\Abstracts\View\Components\Accounts\Index as Component;
use App\Utilities\AccountOverview;
use Illuminate\Pagination\AbstractPaginator;

class Accounts extends Component
{
    /**
     * The groups the accounts are shown in, in order, as key => title.
     *
     * @var array
     */
    public $groups;

    /**
     * Read once per page by getOverview(); kept out of the public properties so it is not
     * handed down to the parts that never use it.
     *
     * @var array|null
     */
    public $overview = null;

    public $reconciliation_range = null;
    
    public function __construct(
        $accounts = null,
        array $groups = [],
        ?array $overview = null,
        $reconciliation_range = null
    ) {
        $this->accounts = $accounts;
        $this->groups = $this->getGroups($groups);
        $this->overview = $this->getOverview();
        $this->reconciliation_range = $this->getReconciliationRange();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.accounts.index.accounts');
    }
    
    /**
     * The accounts are grouped by their type, banks first. Whether an account is kept up to date
     * by an app is told on its own card, so it does not move the account out of its type. A type
     * an app adds gets a group of its own after these.
     */
    protected function getGroups(array $groups): array
    {
        if (! empty($groups)) {
            return $groups;
        }

        return [
            'bank'          => trans_choice('accounts.banks', 2),
            'credit_card'   => trans_choice('accounts.credit_cards', 2),
        ];
    }

    /**
     * The accounts of the page sorted into their groups, each group keeping the order the
     * page is sorted by. A group with nothing in it on this page is left out.
     */
    public function getGroupedAccounts(): array
    {
        $grouped = array_fill_keys(array_keys($this->groups), []);

        foreach ($this->accounts as $account) {
            $grouped[$account->type ?: 'bank'][] = $account;
        }

        return array_filter($grouped);
    }

    public function getGroupTitle(string $group): string
    {
        return $this->groups[$group] ?? $group;
    }

    /**
     * What each account card shows beside the account itself, read for the whole page at
     * once and only when the cards ask for it, so the buttons and the summary cost nothing.
     *
     * @return array<int, array>
     */
    public function getOverview(): array
    {
        $overview = $this->overview;

        if (is_null($this->overview)) {
            $accounts = $this->accounts instanceof AbstractPaginator
                ? $this->accounts->getCollection()
                : collect($this->accounts);

            $overview = (new AccountOverview())->get($accounts);
        }

        return $overview;
    }

    public function getReconciliationRange(): ?array
    {
        return (new AccountOverview())->getReconciliationRange();
    }
}
