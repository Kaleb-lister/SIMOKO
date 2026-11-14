<?php

namespace Tests\Unit;

use App\Services\EtaCalculator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EtaCalculatorTest extends TestCase
{
    public function test_target_reached_when_balance_meets_target(): void
    {
        $result = EtaCalculator::calculate(50000, 50000);
        $this->assertEquals('reached', $result['status']);
        $this->assertEquals(0, $result['days']);
        $this->assertEquals('Target tercapai', $result['message']);

        $resultOver = EtaCalculator::calculate(50000, 60000);
        $this->assertEquals('reached', $resultOver['status']);
    }

    public function test_insufficient_data_when_no_deposits_or_span_less_than_two(): void
    {
        $today = Carbon::parse('2026-11-14');

        // No deposits
        $result = EtaCalculator::calculate(50000, 10000, [], $today);
        $this->assertEquals('insufficient_data', $result['status']);

        // Only 1 deposit today (span = 1 day < 2)
        $deposits = [
            ['amount' => 1000, 'occurred_at' => $today->copy()->setHour(10)],
        ];
        $result2 = EtaCalculator::calculate(50000, 1000, $deposits, $today);
        $this->assertEquals('insufficient_data', $result2['status']);
    }

    public function test_insufficient_data_when_daily_average_zero_or_negative(): void
    {
        $today = Carbon::parse('2026-11-14');

        // Deposits happened outside 7 days window
        $deposits = [
            ['amount' => 1000, 'occurred_at' => $today->copy()->subDays(20)],
            ['amount' => 1000, 'occurred_at' => $today->copy()->subDays(15)],
        ];
        $result = EtaCalculator::calculate(50000, 2000, $deposits, $today);
        $this->assertEquals('insufficient_data', $result['status']);
    }

    public function test_calculates_correct_days_for_normal_case(): void
    {
        $today = Carbon::parse('2026-11-14');

        // Deposits across 3 days: today, yesterday, 2 days ago (span = 3)
        // Total deposits = 3000 -> average = 1000/day
        // Remaining = 50000 - 10000 = 40000
        // Days = 40000 / 1000 = 40 days
        $deposits = [
            ['amount' => 1000, 'occurred_at' => $today->copy()->subDays(2)],
            ['amount' => 1000, 'occurred_at' => $today->copy()->subDays(1)],
            ['amount' => 1000, 'occurred_at' => $today->copy()],
        ];

        $result = EtaCalculator::calculate(50000, 10000, $deposits, $today);
        $this->assertEquals('estimated', $result['status']);
        $this->assertEquals(40, $result['days']);
        $this->assertEquals(1000, $result['daily_average']);
        $this->assertEquals('2026-12-24', $result['target_date']->format('Y-m-d'));
    }

    public function test_handles_days_with_no_deposits_in_window(): void
    {
        $today = Carbon::parse('2026-11-14');

        // First deposit was 6 days ago (span = 7 days)
        // Only 2 deposits of 1000 and 2500 -> total 3500 across 7 days
        // Average = 3500 / 7 = 500/day
        // Target = 10000, balance = 3500 -> remaining = 6500
        // Days = ceil(6500 / 500) = 13 days
        $deposits = [
            ['amount' => 1000, 'occurred_at' => $today->copy()->subDays(6)],
            ['amount' => 2500, 'occurred_at' => $today->copy()],
        ];

        $result = EtaCalculator::calculate(10000, 3500, $deposits, $today);
        $this->assertEquals('estimated', $result['status']);
        $this->assertEquals(13, $result['days']);
        $this->assertEquals(500, $result['daily_average']);
    }
}

