<?php

namespace App\Abstracts\Http;

use App\Abstracts\Http\Response;
use App\Traits\Jobs;
use App\Traits\Permissions;
use App\Traits\Relationships;
use App\Traits\SearchString;
use App\Utilities\Export;
use App\Utilities\Import;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, Jobs, Permissions, Relationships, SearchString, ValidatesRequests;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->assignPermissionsToController();
    }

    /**
     * Generate a pagination collection.
     *
     * @param array|Collection $items
     * @param int $perPage
     * @param int $page
     * @param array $options
     *
     * @return LengthAwarePaginator
     */
    public function paginate($items, $perPage = null, $page = null, $options = [])
    {
        $perPage = $perPage ?: (int) request('limit', setting('default.list_limit', '25'));

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }

    /**
     * Generate a response based on request type like HTML, JSON, or anything else.
     *
     * @param string $view
     * @param array $data
     *
     * @return \Illuminate\Http\Response
     */
    public function response($view, $data = [])
    {
        $class_name = str_replace('Controllers', 'Responses', get_class($this));

        if (class_exists($class_name)) {
            $response = new $class_name($view, $data);
        } else {
            $response = new class($view, $data) extends Response {};
        }

        return $response;
    }

    /**
     * Import the excel file or catch errors
     *
     * @param $class
     * @param $request
     * @param $translation
     *
     * @return array
     */
    public function importExcel($class, $request, $translation)
    {
        return Import::fromExcel($class, $request, $translation);
    }

    /**
     * Export the excel file or catch errors
     *
     * @param $class
     * @param $translation
     * @param $extension
     *
     * @return mixed
     */
    public function exportExcel($class, $translation, $extension = 'xlsx')
    {
        return Export::toExcel($class, $translation, $extension);
    }

    public function setActiveTabForDocuments(): void
    {
        // Added this method to set the active tab for documents
        if (! request()->has('list_records') && ! request()->has('search')) {
            $tab_pins = setting('favorites.tab.' . user()->id, []);
            $tab_pins = ! empty($tab_pins) ? json_decode($tab_pins, true) : [];

            if (! empty($tab_pins) && ! empty($tab_pins[$this->type])) {
                $data = config('type.document.' . $this->type . '.route.params.' . $tab_pins[$this->type]);

                if (! empty($data)) {
                    request()->merge($data);
                }
            }
        }

        if (request()->get('list_records') == 'all') {
            return;
        }

        $status = $this->getSearchStringValue('status');

        if (empty($status)) {
            $search = config('type.document.' . $this->type . '.route.params.unpaid.search');

            request()->offsetSet('search', $search);
            request()->offsetSet('programmatic', '1');
        } else {
            $unpaid = str_replace('status:', '', config('type.document.' . $this->type . '.route.params.unpaid.search'));
            $draft = str_replace('status:', '', config('type.document.' . $this->type . '.route.params.draft.search'));

            if (($status == $unpaid) || ($status == $draft)) {
                return;
            }

            request()->offsetSet('list_records', 'all');
        }
    }

    public function setActiveTabForTransactions(): void
    {
        // Added this method to set the active tab for transactions
        if (! request()->has('list_records') && ! request()->has('search')) {
            $tab_pins = setting('favorites.tab.' . user()->id, []);
            $tab_pins = ! empty($tab_pins) ? json_decode($tab_pins, true) : [];

            if (! empty($tab_pins) && ! empty($tab_pins['transactions'])) {
                $data = config('type.transaction.transactions.route.params.' . $tab_pins['transactions']);

                if (! empty($data)) {
                    request()->merge($data);
                }
            }
        }
    }

    public function setActiveTabForCategories(): void
    {
        // Added this method to set the active tab for categories
        if (! request()->has('list_records') && ! request()->has('search')) {
            $tab_pins = setting('favorites.tab.' . user()->id, []);
            $tab_pins = ! empty($tab_pins) ? json_decode($tab_pins, true) : [];

            if (! empty($tab_pins) && ! empty($tab_pins['categories'])) {
                $tab = $tab_pins['categories'];

                if (! empty($tab)) {
                    request()->offsetSet('search', 'type:' . $tab);
                    request()->offsetSet('programmatic', '1');
                }
            }
        }

        if (request()->get('list_records') == 'all') {
            return;
        }

        $type = $this->getSearchStringValue('type');

        // Type değeri aslında bir tab olabilir, kontrol et
        if (!empty($type)) {
            $types = $this->getTypesForCategoryTab($type);

            if (!empty($types) && count($types) > 0) {
                // Bu bir tab, o tab'a ait tüm type'ları set et
                request()->offsetSet('search', 'type:' . implode(',', $types));
                request()->offsetSet('programmatic', '1');
                return;
            }
        }

        if (empty($type)) {
            request()->offsetSet('search', 'type:income');
            request()->offsetSet('programmatic', '1');
        }
    }

    protected function getTypesForCategoryTab(string $tab): array
    {
        $types = [];
        $configs = config('type.category');

        foreach ($configs as $type => $attr) {
            $typeTab = $attr['tab'] ?? $type;

            if ($typeTab === $tab) {
                $types[] = $type;
            }
        }

        return $types;
    }
}
