<?php

namespace App\Livewire\Portal\Holidays;

use App\Models\Holiday;
use App\Models\Company;
use Livewire\Component;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Index extends Component
{
    use WithDataTable;

    public ?string $date = null;
    public $import_file = null;
    public ?string $calendar_year = null;
    public ?string $calendar_month = null;
    public ?string $name = null;
    public ?string $description = null;
    public ?int $company_id = null;
    public ?int $holiday_id = null;
    public ?Holiday $holiday = null;
    public ?string $role = null;

    protected array $rules = [
        'date' => 'required|date',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'company_id' => 'nullable|exists:companies,id',
    ];

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
        $this->calendar_year = (string) now()->year;
        $this->calendar_month = (string) now()->month;
    }

    public function getCalendarHolidays(): array
    {
        $year = (int) ($this->calendar_year ?? now()->year);
        $month = (int) ($this->calendar_month ?? now()->month);
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $query = Holiday::whereBetween('date', [$start, $end]);
        if ($this->role === 'manager') {
            $query->where(function ($q) {
                $q->whereNull('company_id')
                    ->orWhereIn('company_id', auth()->user()->managerCompanies->pluck('id'));
            });
        }
        if ($this->company_id && $this->company_id !== 'all') {
            $query->where(function ($q) {
                $q->whereNull('company_id')->orWhere('company_id', $this->company_id);
            });
        }
        return $query->get()->keyBy(fn ($h) => $h->date->format('Y-m-d'))->toArray();
    }

    public function getCalendarWeeks(): array
    {
        $year = (int) ($this->calendar_year ?? now()->year);
        $month = (int) ($this->calendar_month ?? now()->month);
        $start = Carbon::createFromDate($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $holidays = $this->getCalendarHolidays();
        $weeks = [];
        $current = $start->copy()->startOfWeek(Carbon::MONDAY);
        $endWeek = $end->copy()->endOfWeek(Carbon::SUNDAY);
        while ($current->lte($endWeek)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateKey = $current->format('Y-m-d');
                $week[] = [
                    'date' => $current->copy(),
                    'isCurrentMonth' => $current->month === $month,
                    'isHoliday' => isset($holidays[$dateKey]),
                    'holiday' => $holidays[$dateKey] ?? null,
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }
        return $weeks;
    }

    public function getHolidays()
    {
        $query = Holiday::query()->orderBy('date', 'desc');
        if ($this->role === 'manager') {
            $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'));
        } elseif ($this->role === 'admin') {
            // Admin sees all
        } else {
            $query->where('id', 0);
        }
        if ($this->company_id && $this->company_id !== 'all') {
            $query->where(function ($q) {
                $q->whereNull('company_id')->orWhere('company_id', $this->company_id);
            });
        }
        return $query->paginate($this->perPage);
    }

    public function store()
    {
        if (! Gate::allows('setting-read')) {
            return abort(401);
        }
        $this->validate();
        Holiday::create([
            'date' => $this->date,
            'name' => $this->name,
            'description' => $this->description,
            'company_id' => $this->company_id ?: null,
        ]);
        $this->clearFields();
        $this->dispatch('showToast', message: __('holidays.holiday_created'), type: 'success');
        $this->dispatch('close-modal', id: 'CreateHolidayModal');
    }

    public function initData($holidayId)
    {
        $this->holiday = Holiday::findOrFail($holidayId);
        $this->holiday_id = $this->holiday->id;
        $this->date = $this->holiday->date->format('Y-m-d');
        $this->name = $this->holiday->name;
        $this->description = $this->holiday->description;
        $this->company_id = $this->holiday->company_id;
    }

    public function update()
    {
        if (! Gate::allows('setting-read')) {
            return abort(401);
        }
        $this->validate();
        $this->holiday->update([
            'date' => $this->date,
            'name' => $this->name,
            'description' => $this->description,
            'company_id' => $this->company_id ?: null,
        ]);
        $this->clearFields();
        $this->dispatch('showToast', message: __('holidays.holiday_updated'), type: 'success');
        $this->dispatch('close-modal', id: 'EditHolidayModal');
    }

    public function delete()
    {
        if (! Gate::allows('setting-read')) {
            return abort(401);
        }
        $this->holiday?->delete();
        $this->clearFields();
        $this->dispatch('showToast', message: __('holidays.holiday_deleted'), type: 'success');
        $this->dispatch('close-modal', id: 'DeleteHolidayModal');
    }

    public function importBulk()
    {
        if (! Gate::allows('setting-read')) {
            return abort(401);
        }
        $this->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:2048',
            'company_id' => 'nullable|exists:companies,id',
        ]);
        $path = $this->import_file->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_shift($rows);
        $created = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            if (count($row) < 2 || empty(trim($row[0] ?? '')) || empty(trim($row[1] ?? ''))) {
                $skipped++;
                continue;
            }
            $dateStr = trim($row[0]);
            $name = trim($row[1]);
            $description = trim($row[2] ?? '');
            try {
                $date = Carbon::parse($dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                $skipped++;
                continue;
            }
            $companyId = $this->company_id ?: null;
            $exists = Holiday::where('date', $date)
                ->where('company_id', $companyId)
                ->exists();
            if ($exists) {
                $skipped++;
                continue;
            }
            Holiday::create([
                'date' => $date,
                'name' => $name,
                'description' => $description ?: null,
                'company_id' => $this->company_id ?: null,
            ]);
            $created++;
        }
        $this->import_file = null;
        $this->dispatch('close-modal', id: 'ImportHolidaysModal');
        $this->dispatch('showToast', message: __('holidays.bulk_import_result', ['created' => $created, 'skipped' => $skipped]), type: 'success');
    }

    public function calendarPrevMonth()
    {
        $d = Carbon::createFromDate((int) $this->calendar_year, (int) $this->calendar_month, 1)->subMonth();
        $this->calendar_year = (string) $d->year;
        $this->calendar_month = (string) $d->month;
    }

    public function calendarNextMonth()
    {
        $d = Carbon::createFromDate((int) $this->calendar_year, (int) $this->calendar_month, 1)->addMonth();
        $this->calendar_year = (string) $d->year;
        $this->calendar_month = (string) $d->month;
    }

    public function clearFields()
    {
        $this->reset(['date', 'name', 'description', 'company_id', 'holiday_id', 'holiday', 'import_file']);
    }

    public function render()
    {
        $holidays = $this->getHolidays();
        $companies = ($this->role === 'admin')
            ? Company::orderBy('name')->get()
            : auth()->user()->managerCompanies()->orderBy('name')->get();

        $calendarWeeks = $this->getCalendarWeeks();
        $monthNames = [
            1 => __('holidays.january'), 2 => __('holidays.february'), 3 => __('holidays.march'),
            4 => __('holidays.april'), 5 => __('holidays.may'), 6 => __('holidays.june'),
            7 => __('holidays.july'), 8 => __('holidays.august'), 9 => __('holidays.september'),
            10 => __('holidays.october'), 11 => __('holidays.november'), 12 => __('holidays.december'),
        ];

        return view('livewire.portal.holidays.index', [
            'holidays' => $holidays,
            'companies' => $companies,
            'calendarWeeks' => $calendarWeeks,
            'monthNames' => $monthNames,
        ])->layout('components.layouts.dashboard');
    }
}
