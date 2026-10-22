<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reconciliation on the account list
    |--------------------------------------------------------------------------
    |
    | The list counts the transactions of each account that are still waiting to be
    | reconciled and offers to reconcile them. Only a fixed number of days is looked at,
    | so an account that has never been reconciled is not answered with every
    | transaction it ever had. Turning it off takes the counter and the button away.
    |
    */

    'reconciliation' => [

        'enabled'   => env('ACCOUNTS_RECONCILIATION_ENABLED', true),

        'days'      => env('ACCOUNTS_RECONCILIATION_DAYS', 30),

    ],

    /*
    |--------------------------------------------------------------------------
    | Balance history on the account list
    |--------------------------------------------------------------------------
    |
    | How many days the balance line drawn on each account card covers.
    |
    */

    'balance_history_days' => env('ACCOUNTS_BALANCE_HISTORY_DAYS', 30),

];
