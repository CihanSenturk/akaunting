<?php

namespace App\Utilities;

use App\Models\Banking\Reconciliation;
use App\Models\Banking\Transaction;
use App\Traits\Currencies;
use App\Traits\Transactions;
use Illuminate\Support\Collection;

/**
 * What the account list shows beside each account, read for the whole page at once.
 *
 * Every figure is gathered with one query for all the accounts rather than one per card, so
 * the list costs the same whether it holds two accounts or forty.
 */
class AccountOverview
{
    use Currencies, Transactions;

    /**
     * The figures of each account, keyed by its id.
     *
     * @return array<int, array{balance_history: array, unreconciled: int|null, statement: array|null}>
     */
    public function get(Collection $accounts): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $ids = $accounts->pluck('id')->all();

        $today = Date::today();
        $days = $this->getHistoryDays();
        $start = $today->copy()->subDays($days - 1);

        $movements = $this->getDailyMovements($ids, $start);
        $statements = $this->getStatements($ids);

        $reconciliation = $this->getReconciliationRange();
        $unreconciled = $reconciliation ? $this->getUnreconciledCounts($ids, $reconciliation['started_at'], $reconciliation['ended_at']) : [];

        $overview = [];

        foreach ($accounts as $account) {
            $overview[$account->id] = [
                'balance_history'   => $this->getBalanceHistory((float) $account->balance, $movements[$account->id] ?? [], $start, $days, $today),
                'unreconciled'      => $reconciliation ? (int) ($unreconciled[$account->id] ?? 0) : null,
                'statement'         => $statements[$account->id] ?? null,
            ];
        }

        return $overview;
    }

    /**
     * The balance of the accounts in the default currency, as each one's own rate has it.
     *
     * A disabled account is left out: it no longer holds money the company counts on.
     */
    public function getTotal(Collection $accounts): float
    {
        return (float) $accounts
            ->filter(fn ($account) => $account->enabled)
            ->sum(fn ($account) => $this->convertToDefault((float) $account->balance, $account->currency_code, $account->currency->rate ?? 1));
    }

    /**
     * The days the reconciliation counter looks at, or null while it is turned off.
     *
     * A fixed window rather than everything since the last reconciliation: an account that was
     * never reconciled would otherwise ask for every transaction it ever had.
     *
     * @return array{started_at: Date, ended_at: Date}|null
     */
    public function getReconciliationRange(): ?array
    {
        if (! config('accounts.reconciliation.enabled', true)) {
            return null;
        }

        $days = max(1, (int) config('accounts.reconciliation.days', 30));

        $today = Date::today();

        return [
            'started_at'    => $today->copy()->subDays($days - 1),
            'ended_at'      => $today,
        ];
    }

    /**
     * The balance at the end of each day, oldest first.
     *
     * Worked out backwards from the balance the account has now: what came in or went out on a
     * day is taken off to reach the day before it. A transaction dated after today is already
     * in the balance, so it is taken off before the first step.
     *
     * @param  array<string, float>  $movements  The net change of each day, keyed by Y-m-d.
     * @return array<int, float>
     */
    public function getBalanceHistory(float $balance, array $movements, Date $start, int $days, Date $today): array
    {
        $last_day = $today->toDateString();

        foreach ($movements as $day => $net) {
            if ($day > $last_day) {
                $balance -= $net;
            }
        }

        $history = [];

        for ($index = $days - 1; $index >= 0; $index--) {
            $day = $start->copy()->addDays($index)->toDateString();

            $history[$index] = round($balance, 4);

            $balance -= $movements[$day] ?? 0;
        }

        ksort($history);

        return array_values($history);
    }

    protected function getHistoryDays(): int
    {
        return max(2, (int) config('accounts.balance_history_days', 30));
    }

    /**
     * What came in less what went out, for each account and day from the start onwards.
     *
     * @return array<int, array<string, float>>
     */
    protected function getDailyMovements(array $ids, Date $start): array
    {
        $income_types = $this->getIncomeTypes();
        $expense_types = $this->getExpenseTypes();

        $placeholders = implode(',', array_fill(0, count($income_types), '?'));

        $rows = Transaction::query()
            ->whereIn('account_id', $ids)
            ->whereIn('type', array_merge($income_types, $expense_types))
            ->where('paid_at', '>=', $start->copy()->startOfDay())
            ->selectRaw('account_id, DATE(paid_at) as day, SUM(CASE WHEN type IN (' . $placeholders . ') THEN amount ELSE -amount END) as net', $income_types)
            ->groupBy('account_id', 'day')
            ->toBase()
            ->get();

        $movements = [];

        foreach ($rows as $row) {
            $movements[$row->account_id][substr((string) $row->day, 0, 10)] = (float) $row->net;
        }

        return $movements;
    }

    /**
     * The closing balance of the last reconciliation that was finished, as the latest
     * statement the company checked each account against.
     *
     * @return array<int, array{balance: float, date: mixed}>
     */
    protected function getStatements(array $ids): array
    {
        return Reconciliation::query()
            ->whereIn('account_id', $ids)
            ->where('reconciled', 1)
            ->orderByDesc('ended_at')
            ->get(['account_id', 'ended_at', 'closing_balance'])
            ->unique('account_id')
            ->mapWithKeys(fn ($reconciliation) => [
                $reconciliation->account_id => [
                    'balance'   => (float) $reconciliation->closing_balance,
                    'date'      => $reconciliation->ended_at,
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    protected function getUnreconciledCounts(array $ids, Date $started_at, Date $ended_at): array
    {
        return Transaction::query()
            ->whereIn('account_id', $ids)
            ->whereIn('type', array_merge($this->getIncomeTypes(), $this->getExpenseTypes()))
            ->where('reconciled', 0)
            ->whereBetween('paid_at', [$started_at->copy()->startOfDay(), $ended_at->copy()->endOfDay()])
            ->selectRaw('account_id, COUNT(*) as total')
            ->groupBy('account_id')
            ->toBase()
            ->pluck('total', 'account_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
