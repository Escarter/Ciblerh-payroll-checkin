<?php

namespace App\Services;

use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class WorkingDayService
{
    /**
     * Get the number of working days in a date period.
     * Working day = weekday (Mon-Fri) that is NOT a holiday.
     *
     * @param  CarbonInterface  $start
     * @param  CarbonInterface  $end
     * @param  int|null  $companyId  Company ID for company-specific holidays; null uses global only
     * @return int
     */
    public function getWorkingDaysInPeriod(CarbonInterface $start, CarbonInterface $end, ?int $companyId = null): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();

        $holidays = Holiday::whereBetween('date', [$start, $end])
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))
            ->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))
            ->flip()
            ->toArray();

        $count = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($current->isWeekday() && ! isset($holidays[$current->format('Y-m-d')])) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Check if a given date is a working day.
     */
    public function isWorkingDay(CarbonInterface $date, ?int $companyId = null): bool
    {
        $date = Carbon::parse($date);
        if (! $date->isWeekday()) {
            return false;
        }

        return ! Holiday::where('date', $date)
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))
            ->exists();
    }
}
