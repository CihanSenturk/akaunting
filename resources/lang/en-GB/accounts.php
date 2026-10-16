<?php

return [

    'account_name'          => 'Account Name',
    'account_balance'       => 'Account Balance',
    'number'                => 'Number',
    'opening_balance'       => 'Opening Balance',
    'current_balance'       => 'Current Balance',
    'bank_name'             => 'Bank Name',
    'bank_phone'            => 'Bank Phone',
    'bank_address'          => 'Bank Address',
    'default_account'       => 'Default Account',
    'incoming'              => 'Incoming',
    'outgoing'              => 'Outgoing',
    'see_performance'       => 'See Performance',
    'create_report'         => 'If you want to see account performance. You can create Income vs Expense report instance.',
    'banks'                 => 'Bank|Banks',
    'credit_cards'          => 'Credit Card|Credit Cards',

    'bank_feed' => [
        'title'             => 'Connect Your Bank Account',
        'description'       => 'Import your bank transactions into Akaunting automatically every day. Reduce manual data entry, match payments to your invoices and bills, and reconcile your account with your bank statement in less time.',
        'action'            => 'Get Bank Feeds',
        'add_without'       => 'Add Account Manually',

        'action_description'        => 'Connect through a secure, read-only bank connection and import transactions automatically.',
        'add_without_description'   => 'Create the account without a bank connection and record its transactions manually. Suitable for cash accounts and banks that are not supported.',

        'benefits' => [
            'sync'          => 'Bank transactions are imported automatically every day.',
            'match'         => 'Imported transactions are matched to your invoices, bills and existing records.',
            'reconcile'     => 'Bank reconciliation is faster, with fewer missing or duplicate entries.',
        ],
    ],

    'form_description' => [
        'general'           => 'Use credit card type for negative opening balance. The number is essential to reconcile accounts correctly. Default account will record all transactions if not selected otherwise.',
        'bank'              => 'You may have multiple bank accounts in more than one banks. Recording information about your bank will make it easier to match the transactions within your bank.',
    ],

    'no_records' => [
        'transactions'      => 'There is no transaction in this account yet. Create a new one now.',
        'transfers'         => 'There is no transfer to/from this account yet. Create a new one now.',
    ],

];
