<?php

namespace App\Abstracts\View\Components\Accounts;

use App\Abstracts\View\Component;
use App\Traits\ViewComponents;

/**
 * The account list, split into the parts the page is put together from, the same way the
 * transaction and document lists are: buttons, content, the groups and a single account.
 *
 * Every part reads its settings from here, so a page or an app can hide or rename a piece
 * without copying the view it sits in.
 */
abstract class Index extends Component
{
    use ViewComponents;

    public const OBJECT_TYPE = 'account';
    public const DEFAULT_TYPE = 'account';
    public const DEFAULT_PLURAL_TYPE = 'accounts';

    /** @var \Illuminate\Support\Collection|\Illuminate\Pagination\LengthAwarePaginator */
    public $accounts;

    /** @var string */
    public $permissionCreate;

    /** @var bool */
    public $checkPermissionCreate;

    /** @var bool */
    public $hideCreate;

    /** @var string */
    public $createRoute;

    /** @var string */
    public $textCreate;

    /** @var bool */
    public $hideSummary;

    /** @var array */
    public $summaryItems;

    /** @var bool */
    public $hideSearchString;

    /** @var bool */
    public $hideBulkAction;

    /** @var string */
    public $searchStringModel;

    /** @var string */
    public $bulkActionClass;

    /** @var bool */
    public $hidePagination;

    public function __construct(
        $accounts = [],
        string $permissionCreate = '', bool $checkPermissionCreate = true,
        bool $hideCreate = false, string $createRoute = '', string $textCreate = '',
        bool $hideSearchString = false, bool $hideBulkAction = false,
        string $searchStringModel = '', string $bulkActionClass = '',
        bool $hidePagination = false
    ) {
        /* -- Main Start -- */
        $this->accounts = ! empty($accounts) ? $accounts : collect();

        $this->permissionCreate = ! empty($permissionCreate) ? $permissionCreate : 'create-banking-accounts';
        /* -- Main End -- */

        /* -- Buttons Start -- */
        $this->checkPermissionCreate = $checkPermissionCreate;

        $this->hideCreate = $hideCreate;
        $this->createRoute = $this->getCreateRoute('banking', $createRoute);
        $this->textCreate = ! empty($textCreate) ? $textCreate : 'general.accounts';
        /* -- Buttons End -- */

        /* -- Content Start -- */
        $this->hideSearchString = $hideSearchString;
        $this->hideBulkAction = $hideBulkAction;

        $this->searchStringModel = ! empty($searchStringModel) ? $searchStringModel : 'App\Models\Banking\Account';

        $this->bulkActionClass = ! empty($bulkActionClass) ? $bulkActionClass : 'App\BulkActions\Banking\Accounts';

        $this->hidePagination = $hidePagination;
        /* -- Content End -- */

        // Set Parent data
        $this->setParentData();
    }

    protected function getCreateRoute($type, $createRoute)
    {
        if (! empty($createRoute)) {
            return $createRoute;
        }

        $route = $this->getRouteFromConfig($type, 'create');

        if (! empty($route)) {
            return $route;
        }

        $route = $this->moduleIsEnabled('bank-feeds') && user()->can('create-bank-feeds-bank-connections')
            ? 'bank-feeds.bank-connections.create'
            : 'accounts.create';

        return $route;
    }
}
