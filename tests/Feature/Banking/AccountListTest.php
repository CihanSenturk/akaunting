<?php

namespace Tests\Feature\Banking;

use App\Models\Banking\Account;
use App\Models\Banking\Reconciliation;
use App\Models\Banking\Transaction;
use App\Imports\Banking\Transactions as TransactionsImport;
use App\Utilities\AccountOverview;
use App\Utilities\Date;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Feature\FeatureTestCase;

class AccountListTest extends FeatureTestCase
{
    public function testItShouldGroupTheAccountsByType()
    {
        $this->loginAs();

        $bank = Account::factory()->enabled()->create(['type' => 'bank']);
        $card = Account::factory()->enabled()->create(['type' => 'credit_card']);

        $this->get(route('accounts.index'))
            ->assertStatus(200)
            ->assertSeeText(trans_choice('accounts.banks', 2))
            ->assertSeeText(trans_choice('accounts.credit_cards', 2))
            ->assertSeeInOrder(['index-account-group-bank', $bank->name, 'index-account-group-credit_card', $card->name], false);
    }

    public function testItShouldOfferToReconcileTheWaitingTransactions()
    {
        $this->loginAs();

        $account = Account::factory()->enabled()->default_currency()->create(['type' => 'bank']);

        $this->createTransaction($account, ['reconciled' => 0]);
        $this->createTransaction($account, ['reconciled' => 0]);
        $this->createTransaction($account, ['reconciled' => 1]);

        $this->get(route('accounts.index'))
            ->assertStatus(200)
            ->assertSee('index-account-reconcile-' . $account->id, false)
            ->assertSeeText(trans_choice('accounts.reconcile_items', 2, ['count' => 2]));
    }

    public function testItShouldNotCountWhatIsOutsideTheWindow()
    {
        $this->loginAs();

        $account = Account::factory()->enabled()->default_currency()->create(['type' => 'bank']);

        $this->createTransaction($account, [
            'reconciled'    => 0,
            'paid_at'       => Date::today()->subDays(config('accounts.reconciliation.days') + 5)->toDateTimeString(),
        ]);

        $this->get(route('accounts.index'))
            ->assertStatus(200)
            ->assertDontSee('index-account-reconcile-' . $account->id, false);
    }

    public function testItShouldShowTheLastStatementOfAReconciledAccount()
    {
        $this->loginAs();

        $account = Account::factory()->enabled()->default_currency()->create(['type' => 'bank']);

        Reconciliation::create([
            'company_id'        => $this->company->id,
            'account_id'        => $account->id,
            'started_at'        => Date::today()->subMonth()->startOfMonth(),
            'ended_at'          => Date::today()->subMonth()->endOfMonth(),
            'closing_balance'   => 1234.5,
            'reconciled'        => 1,
            'created_from'      => 'core::test',
        ]);

        $this->get(route('accounts.index'))
            ->assertStatus(200)
            ->assertSeeText(trans('reconciliations.reconciled'))
            ->assertSee(money(1234.5, $account->currency_code)->format(), false)
            // An account kept by hand can always take another statement
            ->assertSee('index-account-import-' . $account->id, false);
    }

    public function testItShouldOfferAnImportToAnAccountWithNothingToReconcile()
    {
        $this->loginAs();

        $account = Account::factory()->enabled()->create();

        $this->get(route('accounts.index'))
            ->assertStatus(200)
            ->assertSee(route('accounts.import-transactions', $account->id), false);
    }

    public function testItShouldLeaveTheCounterOutWhileItIsTurnedOff()
    {
        config(['accounts.reconciliation.enabled' => false]);

        $this->loginAs();

        $account = Account::factory()->enabled()->default_currency()->create();

        $this->createTransaction($account, ['reconciled' => 0]);

        $this->get(route('accounts.index'))
            ->assertStatus(200)
            ->assertDontSee('index-account-reconcile-' . $account->id, false)
            ->assertDontSeeText(trans('accounts.to_reconcile', ['days' => config('accounts.reconciliation.days')]));
    }

    public function testItShouldReadTheOverviewWithTheSameQueriesForAnyNumberOfAccounts()
    {
        $this->loginAs();

        $few = Account::factory()->count(2)->enabled()->create();
        $many = Account::factory()->count(6)->enabled()->create();

        $this->assertSame($this->countQueries($few), $this->countQueries($many));
    }

    public function testItShouldOpenTheImportForTheAccount()
    {
        $this->loginAs();

        $account = Account::factory()->enabled()->create();

        $this->get(route('accounts.import-transactions', $account->id))
            ->assertRedirect(route('import.create', [
                'group'         => 'banking',
                'type'          => 'transactions',
                'account_id'    => $account->id,
            ]));

        $this->get(route('import.create', ['group' => 'banking', 'type' => 'transactions', 'account_id' => $account->id]))
            ->assertStatus(200)
            ->assertSee($account->name)
            ->assertSee('name="account_id"', false);
    }

    public function testItShouldImportIntoTheAccountItWasStartedFrom()
    {
        Excel::fake();

        $this->loginAs();

        $account = Account::factory()->enabled()->create();

        $response = $this->post(route('transactions.import'), [
            'account_id'    => $account->id,
            'import'        => UploadedFile::fake()->createWithContent(
                'transactions.xlsx',
                File::get(public_path('files/import/transactions.xlsx'))
            ),
        ]);

        $response->assertStatus(200)
            ->assertJson(['redirect' => route('accounts.show', $account->id)]);

        Excel::assertImported('transactions.xlsx', fn (TransactionsImport $import) => $import->account_id === $account->id);
    }

    public function testItShouldPutEveryRowIntoTheChosenAccount()
    {
        $this->loginAs();

        $account = Account::factory()->enabled()->create();
        $other = Account::factory()->enabled()->create();

        $row = (new TransactionsImport($account->id))->map([
            'type'          => 'income',
            'number'        => 'TRA-IMPORT-1',
            'account_name'  => $other->name,
            'paid_at'       => Date::today()->toDateString(),
            'amount'        => 10,
        ]);

        $this->assertSame($account->id, $row['account_id']);
        $this->assertSame($account->currency_code, $row['currency_code']);
    }

    private function createTransaction(Account $account, array $attributes = []): Transaction
    {
        return Transaction::factory()->income()->create(array_merge([
            'account_id'    => $account->id,
            'currency_code' => $account->currency_code,
            'paid_at'       => Date::today()->toDateTimeString(),
        ], $attributes));
    }

    private function countQueries($accounts): int
    {
        $accounts = Account::whereIn('id', $accounts->pluck('id'))
            ->withSum('income_transactions', 'amount')
            ->withSum('expense_transactions', 'amount')
            ->get();

        DB::flushQueryLog();
        DB::enableQueryLog();

        (new AccountOverview())->get($accounts);

        $count = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $count;
    }
}
