<?php

namespace App\Livewire\Portal\Holidays;

use App\Models\Holiday;
use App\Models\Company;
use Livewire\Component;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use App\Imports\Services\AdapterRegistry;
use App\Imports\Services\FieldMappingService;

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
        if (!Gate::allows('setting-read')) {
            return abort(401);
        }

        $this->validate([
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        try {
            // Get the adapter for holidays
            $adapterRegistry = app(AdapterRegistry::class);
            $adapter = $adapterRegistry->getAdapter('holidays');

            if (!$adapter) {
                $this->dispatch('showToast',
                    message: __('common.error_loading_import_adapter'),
                    type: 'danger'
                );
                return;
            }

            // Set context (company scope for this import)
            if ($this->company_id) {
                $adapter->setContext(['company_id' => $this->company_id]);
            }

            $adapter->setUser(auth()->user());
            $adapter->setImportMode('create_only'); // Holidays are typically created fresh

            // Parse and process the file row by row
            $filePath = $this->import_file->getRealPath();
            $rows = array_map('str_getcsv', file($filePath));
            $header = array_shift($rows); // Remove header row

            // Auto-map headers to field definitions
            $fieldMappingService = app(FieldMappingService::class);
            $fieldMappings = $fieldMappingService->autoMap($header, $adapter);

            $created = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($rows as $rowNumber => $values) {
                if (count($values) < 2 || empty(trim($values[0] ?? '')) || empty(trim($values[1] ?? ''))) {
                    $skipped++;
                    continue;
                }

                // Apply field mapping to transform raw row to field-keyed array
                $mappedRow = $fieldMappingService->applyMapping($values, $fieldMappings);

                // Add context
                if ($this->company_id) {
                    $mappedRow['company_id'] = $this->company_id;
                }

                // Validate the row
                $rowErrors = $adapter->validateRow($mappedRow, $rowNumber + 2); // +2 for header + 1-based indexing
                if (!empty($rowErrors)) {
                    $failed++;
                    continue;
                }

                // Validate relationships
                $relationErrors = $adapter->validateRelationships($mappedRow, $rowNumber + 2);
                if (!empty($relationErrors)) {
                    $failed++;
                    continue;
                }

                // Transform the row
                $transformedData = $adapter->transformRow($mappedRow);
                if (isset($transformedData['__error'])) {
                    $failed++;
                    continue;
                }

                // Create or update the record
                $result = $adapter->createOrUpdateRecord($transformedData);
                if ($result) {
                    $created++;
                } else {
                    $skipped++;
                }
            }

            $this->import_file = null;
            $this->dispatch('close-modal', id: 'ImportHolidaysModal');
            
            $message = __('holidays.bulk_import_result', ['created' => $created, 'skipped' => $skipped]);
            if ($failed > 0) {
                $message .= " ({$failed} " . __('common.failed') . ")";
            }

            $this->dispatch('showToast',
                message: $message,
                type: 'success'
            );

        } catch (\Exception $e) {
            $this->dispatch('showToast',
                message: __('common.error_importing_file') . ': ' . $e->getMessage(),
                type: 'danger'
            );
            \Log::error('Holiday import failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
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
