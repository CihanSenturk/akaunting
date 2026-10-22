<?php

namespace Tests\Unit\Utilities;

use App\Utilities\AccountOverview;
use App\Utilities\Date;
use PHPUnit\Framework\TestCase;

class AccountOverviewTest extends TestCase
{
    public function testItShouldDrawAnAccountWithoutMovementsFlat(): void
    {
        $history = $this->history(0, []);

        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0], $history);
    }

    public function testItShouldKeepAnOpeningBalanceOlderThanTheRange(): void
    {
        // Nothing moved in the five days, so the opening balance is all there is on each one
        $history = $this->history(1500, []);

        $this->assertSame([1500.0, 1500.0, 1500.0, 1500.0, 1500.0], $history);
    }

    public function testItShouldRiseWithIncome(): void
    {
        $history = $this->history(300, [
            '2026-10-14' => 100,
            '2026-10-16' => 200,
        ]);

        $this->assertSame([0.0, 100.0, 100.0, 300.0, 300.0], $history);
    }

    public function testItShouldFallBelowZeroWithExpenses(): void
    {
        $history = $this->history(-250, [
            '2026-10-15' => -400,
            '2026-10-17' => 50,
        ]);

        $this->assertSame([100.0, 100.0, -300.0, -300.0, -250.0], $history);
        $this->assertLessThan(0, end($history));
    }

    public function testItShouldLeaveOutWhatIsDatedAfterToday(): void
    {
        // The balance already counts the 1000 due tomorrow, which today has not seen yet
        $history = $this->history(1100, [
            '2026-10-17' => 100,
            '2026-10-18' => 1000,
        ]);

        $this->assertSame([0.0, 0.0, 0.0, 0.0, 100.0], $history);
    }

    public function testItShouldIgnoreMovementsBeforeTheRange(): void
    {
        $history = $this->history(100, [
            '2026-10-01' => 999,
        ]);

        $this->assertSame([100.0, 100.0, 100.0, 100.0, 100.0], $history);
    }

    /**
     * Five days, 13 to 17 October 2026.
     */
    private function history(float $balance, array $movements): array
    {
        $today = Date::parse('2026-10-17');

        return (new AccountOverview())->getBalanceHistory($balance, $movements, $today->copy()->subDays(4), 5, $today);
    }
}
