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
        'title'             => 'Connect Your Bank',
        'description'       => 'Connect your bank to Akaunting and let your transactions come in on their own, day after day, instead of being typed in one by one.',
        'action'            => 'Get Bank Feeds',
        'add_without'       => 'Add without bank feed',

        'action_description'        => 'Connect your bank and let the transactions come in on their own.',
        'add_without_description'   => 'Enter the account by hand and keep its transactions up to date yourself.',

        'benefits' => [
            'sync'          => 'Your bank transactions are fetched and kept up to date automatically.',
            'match'         => 'Incoming transactions are matched with your invoices and bills.',
            'reconcile'     => 'Your balance always follows your bank, so reconciliation takes minutes.',
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
