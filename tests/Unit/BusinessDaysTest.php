<?php

namespace Tests\Unit;

use App\Support\BusinessDays;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class BusinessDaysTest extends TestCase
{
    public function test_weekends_and_national_holidays_are_skipped(): void
    {
        // Thu 19/11/2026: 8 and 9 December, 25 December and 1 January are holidays.
        $this->assertSame('2027-01-06', BusinessDays::after(Carbon::parse('2026-11-19'), 30)->toDateString());
    }

    public function test_holy_thursday_and_good_friday_are_holidays(): void
    {
        // Easter 2026 is 5 April.
        $this->assertFalse(BusinessDays::isWorkingDay(Carbon::parse('2026-04-02')));
        $this->assertFalse(BusinessDays::isWorkingDay(Carbon::parse('2026-04-03')));
        $this->assertTrue(BusinessDays::isWorkingDay(Carbon::parse('2026-04-06')));
        $this->assertSame('2026-04-06', BusinessDays::after(Carbon::parse('2026-03-30'), 3)->toDateString());
    }

    public function test_days_between_counts_forward_and_backward(): void
    {
        $this->assertSame(5, BusinessDays::between(Carbon::parse('2026-11-13'), Carbon::parse('2026-11-20')));
        $this->assertSame(0, BusinessDays::between(Carbon::parse('2026-11-20'), Carbon::parse('2026-11-20')));
        $this->assertSame(-5, BusinessDays::between(Carbon::parse('2026-11-20'), Carbon::parse('2026-11-13')));
    }
}
