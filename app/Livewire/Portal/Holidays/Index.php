<?php

namespace App\Livewire\Portal\Holidays;

use App\Models\Holiday;
use App\Models\Company;
use Livewire\Component;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?string $date = null;
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

    public function clearFields()
    {
        $this->reset(['date', 'name', 'description', 'company_id', 'holiday_id', 'holiday']);
    }

    public function render()
    {
        $holidays = $this->getHolidays();
        $companies = ($this->role === 'admin')
            ? Company::orderBy('name')->get()
            : auth()->user()->managerCompanies()->orderBy('name')->get();

        return view('livewire.portal.holidays.index', [
            'holidays' => $holidays,
            'companies' => $companies,
        ])->layout('components.layouts.dashboard');
    }
}
