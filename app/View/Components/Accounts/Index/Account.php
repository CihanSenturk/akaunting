<?php

namespace App\View\Components\Accounts\Index;

use Akaunting\Apexcharts\Chart;
use App\Abstracts\View\Component;
use App\Traits\Charts;
use App\Utilities\Date;

/**
 * One account on the list: what it holds, what is waiting on it, and how its balance has moved.
 *
 * Kept apart from the list that holds it, so the look of an account can change without
 * touching how the page groups and pages them. The figures are read by the list for every
 * account at once and handed in through $overview.
 */
class Account extends Component
{
    use Charts;

    /**
     * The colours of the balance line, the purple and the red of the application's palette.
     */
    public const CHART_COLOR = '#55588b';
    public const CHART_COLOR_OVERDRAWN = '#cc0000';

    /** @var \App\Models\Banking\Account */
    public $account;

    /** @var bool */
    public $isDefault;

    /** @var array */
    public $history;

    /** @var int|null */
    public $unreconciled;

    /** @var array|null */
    public $statement;

    /** @var array|null */
    public $reconciliationRange;

    /** @var bool */
    public $isOverdrawn;

    /** @var Date */
    public $historyStart;

    /** @var Date */
    public $historyEnd;

    /** @var Chart|null */
    public $chart;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        $account,
        array $overview = [],
        ?array $reconciliationRange = null
    ) {
        $this->account = $account;
        $this->isDefault = setting('default.account') == $account->id;

        $this->history = $overview['balance_history'] ?? [];
        $this->unreconciled = $overview['unreconciled'] ?? null;
        $this->statement = $overview['statement'] ?? null;
        $this->reconciliationRange = $reconciliationRange;

        // A credit card is meant to be owed on, so a balance below zero is how it normally
        // stands and is not drawn as a warning the way an overdrawn bank account is
        $this->isOverdrawn = $account->type !== 'credit_card' && $account->balance < 0;

        $this->historyEnd = Date::today();
        $this->historyStart = $this->historyEnd->copy()->subDays(max(count($this->history) - 1, 0));

        $this->chart = $this->getChart();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.accounts.index.account');
    }

    /**
     * How the balance moved over the days, drawn the way the dashboard widgets draw theirs but
     * without axes and grid, to fit the card. Hovering a day tells its date and balance.
     */
    protected function getChart(): ?Chart
    {
        if (count($this->history) < 2) {
            return null;
        }

        $labels = [];

        foreach (array_keys($this->history) as $day) {
            $labels[] = company_date($this->historyStart->copy()->addDays($day));
        }

        $chart = new Chart();

        $chart->setType('area')
            ->setDefaultLocale($this->getDefaultLocaleOfChart())
            ->setLocales($this->getLocaleTranslationOfChart())
            ->setHeight(64)
            ->setSparkline(['enabled' => true])
            ->setLabels($labels)
            ->setColors([$this->isOverdrawn ? static::CHART_COLOR_OVERDRAWN : static::CHART_COLOR])
            ->setDataset(trans('accounts.current_balance'), 'area', array_values($this->history))
            // The application's chart defaults show a toolbar, a grid and a dot on every point,
            // which a line this small has no room for; only the hovered day is marked
            ->setOptions([
                'chart' => [
                    'toolbar' => ['show' => false],
                    'zoom' => ['enabled' => false],
                ],
                'grid' => [
                    'show' => false,
                ],
                'markers' => [
                    'size' => 0,
                    'hover' => ['size' => 4],
                ],
                'legend' => [
                    'show' => false,
                ],
                'dataLabels' => [
                    'enabled' => false,
                ],
                'stroke' => [
                    'width' => 2,
                    'curve' => 'straight',
                ],
                'fill' => [
                    'type'      => 'solid',
                    'opacity'   => 0.12,
                ],
                // Told for the day under the pointer anywhere along the line, since there are no
                // dots left to point at
                'tooltip' => [
                    'shared' => true,
                    'intersect' => false,
                    'x' => [
                        'show' => true,
                    ],
                    'y' => [
                        'formatter' => $this->getChartLabelFormatter('money', null, $this->account->currency_code),
                    ],
                ],
            ]);

        return $chart;
    }
}
