<?php

namespace App\Livewire\Components;

use App\Models\Absence;
use App\Models\AdvanceSalary;
use App\Models\AnnualDeclaration;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\DownloadJob;
use App\Models\Holiday;
use App\Models\ImportJob;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\Payslip;
use App\Models\Role;
use App\Models\Service;
use App\Models\Ticking;
use App\Models\User;
use App\Utils\PermissionHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $search = '';
    public array $results = [];
    public bool $isOpen = false;
    public int $selectedIndex = -1;

    protected $listeners = [
        'openGlobalSearch' => 'openSearch',
        'closeGlobalSearch' => 'closeSearch',
    ];

    public function mount()
    {
        $this->loadResults();
    }

    public function updatedSearch()
    {
        $this->selectedIndex = -1;
        $this->loadResults();
    }

    public function openSearch()
    {
        $this->isOpen = true;
        $this->search = '';
        $this->selectedIndex = -1;
        $this->loadResults();
    }

    public function closeSearch()
    {
        $this->isOpen = false;
        $this->search = '';
        $this->selectedIndex = -1;
    }

    public function selectNext($step = 1)
    {
        if (count($this->results) > 0) {
            $this->selectedIndex = min(count($this->results) - 1, $this->selectedIndex + $step);
        }
    }

    public function selectPrevious($step = 1)
    {
        if (count($this->results) > 0) {
            $this->selectedIndex = max(0, $this->selectedIndex - $step);
        }
    }

    public function selectFirst()
    {
        if (count($this->results) > 0) {
            $this->selectedIndex = 0;
        }
    }

    public function selectLast()
    {
        if (count($this->results) > 0) {
            $this->selectedIndex = count($this->results) - 1;
        }
    }

    public function selectItem($index = null)
    {
        $targetIndex = $index ?? $this->selectedIndex;

        if (isset($this->results[$targetIndex])) {
            $item = $this->results[$targetIndex];
            $this->closeSearch();

            // Redirect to the selected item's URL
            return redirect($item['url']);
        }
    }

    private function loadResults()
    {
        $allItems = PermissionHelper::accessibleGlobalSearchLinks();
        $trimmedSearch = trim($this->search);

        if (empty($trimmedSearch)) {
            // Show all accessible items when no search term
            $this->results = array_slice($allItems, 0, 20);
            return;
        }

        $searchTerm = strtolower($trimmedSearch);
        $filtered = [];

        foreach ($allItems as $item) {
            $score = $this->calculateSearchScore($item, $searchTerm);
            if ($score > 0) {
                $item['search_score'] = $score;
                $filtered[] = $item;
            }
        }

        // Sort by search score (descending)
        usort($filtered, fn($a, $b) => $b['search_score'] <=> $a['search_score']);

        $modelRecords = $this->searchRecords($trimmedSearch);

        // Blend top static links with model records (up to 25 total)
        $this->results = array_slice(array_merge(array_slice($filtered, 0, 8), $modelRecords), 0, 25);
    }

    /**
     * Search records across models based on user role and permissions.
     */
    private function searchRecords(string $term): array
    {
        $user = auth()->user();

        if (mb_strlen($term) < 2 || ! $user) {
            return [];
        }

        $records = [];
        $isPrivileged = $user->hasAnyRole(['admin', 'manager', 'supervisor']);

        if ($isPrivileged) {
            $records = array_merge($records, $this->searchUsers($term, $user));
            $records = array_merge($records, $this->searchCompanies($term, $user));
            $records = array_merge($records, $this->searchDepartments($term, $user));
            $records = array_merge($records, $this->searchServices($term, $user));
            $records = array_merge($records, $this->searchLeaves($term, $user, true));
            $records = array_merge($records, $this->searchLeaveTypes($term, $user));
            $records = array_merge($records, $this->searchAbsences($term, $user, true));
            $records = array_merge($records, $this->searchOvertimes($term, $user, true));
            $records = array_merge($records, $this->searchAdvanceSalaries($term, $user, true));
            $records = array_merge($records, $this->searchChecklogs($term, $user, true));
            $records = array_merge($records, $this->searchPayslips($term, $user, true));
            $records = array_merge($records, $this->searchAnnualDeclarations($term, $user, true));
            $records = array_merge($records, $this->searchHolidays($term, $user, true));
            $records = array_merge($records, $this->searchRoles($term, $user));
            $records = array_merge($records, $this->searchImportJobs($term, $user));
            $records = array_merge($records, $this->searchDownloadJobs($term, $user));
            $records = array_merge($records, $this->searchAuditLogs($term, $user, true));
        } else {
            // Regular employee searching their own records
            $records = array_merge($records, $this->searchEmployeeSelf($term, $user));
            $records = array_merge($records, $this->searchLeaves($term, $user, false));
            $records = array_merge($records, $this->searchAbsences($term, $user, false));
            $records = array_merge($records, $this->searchOvertimes($term, $user, false));
            $records = array_merge($records, $this->searchAdvanceSalaries($term, $user, false));
            $records = array_merge($records, $this->searchChecklogs($term, $user, false));
            $records = array_merge($records, $this->searchPayslips($term, $user, false));
            $records = array_merge($records, $this->searchAnnualDeclarations($term, $user, false));
            $records = array_merge($records, $this->searchHolidays($term, $user, false));
            $records = array_merge($records, $this->searchAuditLogs($term, $user, false));
        }

        return $records;
    }

    /**
     * Search Users/Employees (Admin, Manager, Supervisor).
     */
    protected function searchUsers(string $term, User $authUser): array
    {
        $records = [];
        try {
            $query = User::search($term, $authUser)->with(['company', 'department'])->limit(5);
            foreach ($query->get() as $u) {
                $meta = [];
                if (!empty($u->matricule)) {
                    $meta[] = '[' . $u->matricule . ']';
                }
                if (!empty($u->email)) {
                    $meta[] = $u->email;
                }
                if ($u->company?->name) {
                    $meta[] = '· ' . $u->company->name;
                }
                if ($u->department?->name) {
                    $meta[] = '/ ' . $u->department->name;
                }

                $filterParam = $u->matricule ?: ($u->email ?: $u->last_name);
                $records[] = $this->record(
                    'user-' . $u->id,
                    $u->name,
                    implode(' ', $meta),
                    __('common.employees'),
                    route('portal.all-employees', ['q' => $filterParam]),
                    'fas fa-user'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Employee searching their own profile.
     */
    protected function searchEmployeeSelf(string $term, User $authUser): array
    {
        $records = [];
        $lowerTerm = strtolower($term);
        $matches = str_contains(strtolower($authUser->name), $lowerTerm)
            || str_contains(strtolower($authUser->email ?? ''), $lowerTerm)
            || str_contains(strtolower($authUser->matricule ?? ''), $lowerTerm);

        if ($matches) {
            $records[] = $this->record(
                'employee-self-' . $authUser->id,
                $authUser->name . ' (' . __('common.profile') . ')',
                trim(($authUser->matricule ? '[' . $authUser->matricule . '] ' : '') . ($authUser->email ?? '')),
                __('common.profile'),
                route('employee.profile'),
                'fas fa-user-circle'
            );
        }
        return $records;
    }

    /**
     * Search Companies (Admin & Manager).
     */
    protected function searchCompanies(string $term, User $authUser): array
    {
        $records = [];
        if ($authUser->hasRole('supervisor') && ! $authUser->hasAnyRole(['admin', 'manager'])) {
            return [];
        }

        try {
            $like = '%' . $term . '%';
            $companies = Company::manager()->where('name', 'like', $like)->limit(3)->get();
            foreach ($companies as $c) {
                $desc = trim(($c->email ?: '') . ($c->phone ? ' · ' . $c->phone : ''));
                $records[] = $this->record(
                    'company-' . $c->id,
                    $c->name,
                    $desc,
                    __('common.companies'),
                    route('portal.companies.index', ['q' => $c->name]),
                    'fas fa-building'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Departments (Admin, Manager, Supervisor).
     */
    protected function searchDepartments(string $term, User $authUser): array
    {
        $records = [];
        try {
            $isSupervisor = $authUser->hasRole('supervisor') && ! $authUser->hasAnyRole(['admin', 'manager']);
            $departments = Department::query()->with('company');
            $isSupervisor ? $departments->supervisor() : $departments->manager();
            $departments->where('name', 'like', '%' . $term . '%')->limit(3)->get()->each(function ($d) use (&$records, $isSupervisor) {
                $url = $isSupervisor
                    ? route('portal.departments.supervisor', ['q' => $d->name])
                    : ($d->company ? route('portal.departments.index', ['company_uuid' => $d->company->uuid, 'q' => $d->name]) : route('portal.departments.index', ['q' => $d->name]));
                if ($url) {
                    $records[] = $this->record(
                        'department-' . $d->id,
                        $d->name,
                        $d->company?->name ?? '',
                        __('common.departments'),
                        $url,
                        'fas fa-sitemap'
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Services (Admin, Manager, Supervisor).
     */
    protected function searchServices(string $term, User $authUser): array
    {
        $records = [];
        try {
            $isSupervisor = $authUser->hasRole('supervisor') && ! $authUser->hasAnyRole(['admin', 'manager']);
            $services = Service::query()->with('department');
            $isSupervisor ? $services->supervisor() : $services->manager();
            $services->where('name', 'like', '%' . $term . '%')->limit(3)->get()->each(function ($s) use (&$records) {
                if ($s->department) {
                    $records[] = $this->record(
                        'service-' . $s->id,
                        $s->name,
                        $s->department->name,
                        __('common.services'),
                        route('portal.services.index', ['department_uuid' => $s->department->uuid, 'q' => $s->name]),
                        'fas fa-briefcase'
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Leaves (Portal & Employee).
     */
    protected function searchLeaves(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $leaves = Leave::search($term)->with(['user', 'leaveType'])->latest('id')->limit(4)->get();
                foreach ($leaves as $l) {
                    $dateRange = '';
                    if ($l->start_date && $l->end_date) {
                        try {
                            $dateRange = Carbon::parse($l->start_date)->format('d/m/Y') . ' → ' . Carbon::parse($l->end_date)->format('d/m/Y');
                        } catch (\Throwable) {
                            $dateRange = $l->start_date . ' - ' . $l->end_date;
                        }
                    }
                    $desc = trim($dateRange . ($l->leave_reason ? ' · ' . Str::limit($l->leave_reason, 40) : ''));
                    $label = ($l->user?->name ?: __('common.leaves')) . ($l->leaveType?->name ? ' (' . $l->leaveType->name . ')' : '');
                    $records[] = $this->record(
                        'leave-' . $l->id,
                        $label,
                        $desc,
                        __('common.leaves'),
                        route('portal.leaves.index', ['q' => $l->user?->matricule ?: ($l->user?->last_name ?: $term)]),
                        'fas fa-calendar-check'
                    );
                }
            } else {
                $leaves = Leave::where('user_id', $authUser->id)->search($term)->with('leaveType')->latest('id')->limit(3)->get();
                foreach ($leaves as $l) {
                    $dateRange = '';
                    if ($l->start_date && $l->end_date) {
                        try {
                            $dateRange = Carbon::parse($l->start_date)->format('d/m/Y') . ' → ' . Carbon::parse($l->end_date)->format('d/m/Y');
                        } catch (\Throwable) {
                            $dateRange = $l->start_date . ' - ' . $l->end_date;
                        }
                    }
                    $desc = trim($dateRange . ($l->leave_reason ? ' · ' . Str::limit($l->leave_reason, 40) : ''));
                    $records[] = $this->record(
                        'employee-leave-' . $l->id,
                        $l->leaveType?->name ?: __('common.leaves'),
                        $desc,
                        __('common.leaves'),
                        route('employee.leaves', ['q' => $term]),
                        'fas fa-calendar-check'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Leave Types (Admin & Manager).
     */
    protected function searchLeaveTypes(string $term, User $authUser): array
    {
        $records = [];
        try {
            $types = LeaveType::search($term)->limit(3)->get();
            foreach ($types as $lt) {
                $records[] = $this->record(
                    'leavetype-' . $lt->id,
                    $lt->name,
                    $lt->days ? ($lt->days . ' ' . __('common.days')) : '',
                    __('common.leaves'),
                    route('portal.leaves.types', ['q' => $lt->name]),
                    'fas fa-tags'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Absences (Portal & Employee).
     */
    protected function searchAbsences(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $absences = Absence::search($term)->with('user')->latest('id')->limit(4)->get();
                foreach ($absences as $a) {
                    $date = '';
                    if ($a->absence_date) {
                        try {
                            $date = Carbon::parse($a->absence_date)->format('d/m/Y');
                        } catch (\Throwable) {
                            $date = (string) $a->absence_date;
                        }
                    }
                    $status = $a->approval_status ? ' [' . ucfirst($a->approval_status) . ']' : '';
                    $desc = trim($date . ($a->absence_reason ? ' · ' . Str::limit($a->absence_reason, 40) : ''));
                    $label = ($a->user?->name ?: __('common.absences')) . $status;
                    $records[] = $this->record(
                        'absence-' . $a->id,
                        $label,
                        $desc,
                        __('common.absences'),
                        route('portal.absences.index', ['q' => $a->user?->matricule ?: ($a->user?->last_name ?: $term)]),
                        'fas fa-user-clock'
                    );
                }
            } else {
                $absences = Absence::where('user_id', $authUser->id)->search($term)->latest('id')->limit(3)->get();
                foreach ($absences as $a) {
                    $date = '';
                    if ($a->absence_date) {
                        try {
                            $date = Carbon::parse($a->absence_date)->format('d/m/Y');
                        } catch (\Throwable) {
                            $date = (string) $a->absence_date;
                        }
                    }
                    $status = $a->approval_status ? ' [' . ucfirst($a->approval_status) . ']' : '';
                    $desc = trim($date . ($a->absence_reason ? ' · ' . Str::limit($a->absence_reason, 40) : ''));
                    $records[] = $this->record(
                        'employee-absence-' . $a->id,
                        __('common.absences') . $status,
                        $desc,
                        __('common.absences'),
                        route('employee.absences', ['q' => $term]),
                        'fas fa-user-clock'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Overtimes (Portal & Employee).
     */
    protected function searchOvertimes(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $overtimes = Overtime::search($term)->with(['user', 'department'])->latest('id')->limit(4)->get();
                foreach ($overtimes as $o) {
                    $time = '';
                    if ($o->start_time) {
                        try {
                            $time = Carbon::parse($o->start_time)->format('d/m/Y H:i');
                        } catch (\Throwable) {
                            $time = (string) $o->start_time;
                        }
                    }
                    $hours = $o->hours ? ' (' . $o->hours . 'h)' : '';
                    $desc = trim($time . ($o->department?->name ? ' · ' . $o->department->name : ''));
                    $label = ($o->user?->name ?: __('common.overtime')) . $hours;
                    $records[] = $this->record(
                        'overtime-' . $o->id,
                        $label,
                        $desc,
                        __('common.overtime'),
                        route('portal.overtimes.index', ['q' => $o->user?->matricule ?: ($o->user?->last_name ?: $term)]),
                        'fas fa-business-time'
                    );
                }
            } else {
                $overtimes = Overtime::where('user_id', $authUser->id)->search($term)->latest('id')->limit(3)->get();
                foreach ($overtimes as $o) {
                    $time = '';
                    if ($o->start_time) {
                        try {
                            $time = Carbon::parse($o->start_time)->format('d/m/Y H:i');
                        } catch (\Throwable) {
                            $time = (string) $o->start_time;
                        }
                    }
                    $hours = $o->hours ? ' (' . $o->hours . 'h)' : '';
                    $records[] = $this->record(
                        'employee-overtime-' . $o->id,
                        __('common.overtime') . $hours,
                        $time,
                        __('common.overtime'),
                        route('employee.overtimes', ['q' => $term]),
                        'fas fa-business-time'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Advance Salaries (Portal & Employee).
     */
    protected function searchAdvanceSalaries(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $advances = AdvanceSalary::search($term)->with('user')->latest('id')->limit(4)->get();
                foreach ($advances as $adv) {
                    $amount = $adv->amount ? number_format($adv->amount, 0, ',', ' ') . ' XAF' : '';
                    $desc = trim(($adv->reason ? Str::limit($adv->reason, 35) : '') . ($adv->beneficiary_name ? ' · ' . $adv->beneficiary_name : ''));
                    $label = ($adv->user?->name ?: __('common.advance_salary')) . ($amount ? ' · ' . $amount : '');
                    $records[] = $this->record(
                        'advancesalary-' . $adv->id,
                        $label,
                        $desc,
                        __('common.advance_salary'),
                        route('portal.advance-salaries.index', ['q' => $adv->user?->matricule ?: ($adv->user?->last_name ?: $term)]),
                        'fas fa-money-bill-wave'
                    );
                }
            } else {
                $advances = AdvanceSalary::where('user_id', $authUser->id)->search($term)->latest('id')->limit(3)->get();
                foreach ($advances as $adv) {
                    $amount = $adv->amount ? number_format($adv->amount, 0, ',', ' ') . ' XAF' : '';
                    $records[] = $this->record(
                        'employee-advancesalary-' . $adv->id,
                        __('common.advance_salary') . ($amount ? ' · ' . $amount : ''),
                        $adv->reason ? Str::limit($adv->reason, 40) : '',
                        __('common.advance_salary'),
                        route('employee.advance-salaries', ['q' => $term]),
                        'fas fa-money-bill-wave'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Attendance / Checklogs (Portal & Employee).
     */
    protected function searchChecklogs(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $tickings = Ticking::search($term)->with('user')->latest('start_time')->limit(4)->get();
                foreach ($tickings as $t) {
                    $date = '';
                    $timeRange = '';
                    if ($t->start_time) {
                        try {
                            $date = Carbon::parse($t->start_time)->format('d/m/Y');
                            $timeRange = Carbon::parse($t->start_time)->format('H:i') . ($t->end_time ? ' - ' . Carbon::parse($t->end_time)->format('H:i') : ' (in progress)');
                        } catch (\Throwable) {
                            $date = (string) $t->start_time;
                        }
                    }
                    $desc = trim($timeRange . ($t->company_name ? ' · ' . $t->company_name : ''));
                    $label = ($t->user?->name ?: __('common.check_ins')) . ($date ? ' · ' . $date : '');
                    $records[] = $this->record(
                        'ticking-' . $t->id,
                        $label,
                        $desc,
                        __('common.check_ins'),
                        route('portal.checklogs.index', ['q' => $t->user?->matricule ?: ($t->user?->last_name ?: $term)]),
                        'fas fa-fingerprint'
                    );
                }
            } else {
                $tickings = Ticking::where('user_id', $authUser->id)->search($term)->latest('start_time')->limit(3)->get();
                foreach ($tickings as $t) {
                    $date = '';
                    $timeRange = '';
                    if ($t->start_time) {
                        try {
                            $date = Carbon::parse($t->start_time)->format('d/m/Y');
                            $timeRange = Carbon::parse($t->start_time)->format('H:i') . ($t->end_time ? ' - ' . Carbon::parse($t->end_time)->format('H:i') : ' (in progress)');
                        } catch (\Throwable) {
                            $date = (string) $t->start_time;
                        }
                    }
                    $records[] = $this->record(
                        'employee-ticking-' . $t->id,
                        __('common.check_ins') . ' · ' . $date,
                        $timeRange,
                        __('common.check_ins'),
                        route('employee.checklogs', ['q' => $term]),
                        'fas fa-fingerprint'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Payslips (Portal & Employee).
     */
    protected function searchPayslips(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $payslips = Payslip::search($term)->latest('id')->limit(4)->get();
                foreach ($payslips as $p) {
                    $name = trim($p->first_name . ' ' . $p->last_name);
                    $label = ($name ?: 'Payslip') . ($p->month ? ' · ' . $p->month : '');
                    $net = $p->net_pay ? number_format($p->net_pay, 0, ',', ' ') . ' XAF' : '';
                    $meta = [];
                    if ($p->matricule) {
                        $meta[] = '[' . $p->matricule . ']';
                    }
                    if ($net) {
                        $meta[] = $net;
                    }
                    if ($p->email_sent_status) {
                        $meta[] = '· Sent';
                    }
                    $records[] = $this->record(
                        'payslip-' . $p->id,
                        $label,
                        implode(' ', $meta),
                        __('common.payslips'),
                        route('portal.payslips.history', ['q' => $p->matricule ?: ($p->last_name ?: $term)]),
                        'fas fa-file-invoice-dollar'
                    );
                }
            } else {
                $payslips = Payslip::where(function ($q) use ($authUser) {
                    $q->where('employee_id', $authUser->id);
                    if ($authUser->matricule) {
                        $q->orWhere('matricule', $authUser->matricule);
                    }
                })->search($term)->latest('id')->limit(3)->get();
                foreach ($payslips as $p) {
                    $label = __('common.payslips') . ($p->month ? ' · ' . $p->month : '');
                    $net = $p->net_pay ? number_format($p->net_pay, 0, ',', ' ') . ' XAF' : '';
                    $records[] = $this->record(
                        'employee-payslip-' . $p->id,
                        $label,
                        $net,
                        __('common.payslips'),
                        route('employee.payslips', ['q' => $p->month ?: $term]),
                        'fas fa-file-invoice-dollar'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Annual Declarations (Portal & Employee).
     */
    protected function searchAnnualDeclarations(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                $query = AnnualDeclaration::query()->with('company');
                if ($authUser->hasRole('admin')) {
                    // all
                } elseif ($authUser->hasRole('manager')) {
                    $query->manager();
                } elseif ($authUser->hasRole('supervisor')) {
                    $query->supervisor();
                }
                $like = '%' . $term . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('matricule', 'like', $like)
                      ->orWhere('first_name', 'like', $like)
                      ->orWhere('last_name', 'like', $like)
                      ->orWhere('extracted_name', 'like', $like)
                      ->orWhere('year', 'like', $like);
                });
                foreach ($query->latest('id')->limit(4)->get() as $ad) {
                    $name = trim($ad->first_name . ' ' . $ad->last_name) ?: ($ad->extracted_name ?: ($ad->matricule ?: 'Declaration'));
                    $label = $name . ($ad->year ? ' · ' . $ad->year : '');
                    $meta = [];
                    if ($ad->gross_amount) {
                        $meta[] = 'Gross: ' . number_format($ad->gross_amount, 0, ',', ' ') . ' XAF';
                    }
                    if ($ad->company?->name) {
                        $meta[] = '· ' . $ad->company->name;
                    }
                    $records[] = $this->record(
                        'annual-decl-' . $ad->id,
                        $label,
                        implode(' ', $meta),
                        'Annual Declarations',
                        route('portal.annual-declarations.index', ['q' => $ad->matricule ?: ($ad->last_name ?: $ad->year)]),
                        'fas fa-file-contract'
                    );
                }
            } else {
                $query = AnnualDeclaration::visibleToEmployee($authUser)->where(function ($q) use ($term) {
                    $like = '%' . $term . '%';
                    $q->where('year', 'like', $like)->orWhere('extracted_name', 'like', $like);
                });
                foreach ($query->latest('id')->limit(3)->get() as $ad) {
                    $meta = $ad->gross_amount ? 'Gross: ' . number_format($ad->gross_amount, 0, ',', ' ') . ' XAF' : '';
                    $records[] = $this->record(
                        'employee-annual-decl-' . $ad->id,
                        'Annual Declaration · ' . $ad->year,
                        $meta,
                        'Annual Declarations',
                        route('employee.annual-declarations', ['q' => (string) $ad->year]),
                        'fas fa-file-contract'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Holidays (Admin, Manager, Supervisor, Employee).
     */
    protected function searchHolidays(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            $query = Holiday::query();
            if ($authUser->hasRole('admin')) {
                // all
            } elseif ($authUser->hasRole('manager')) {
                $managerCompanyIds = $authUser->managerCompanies->pluck('id');
                $query->where(fn($q) => $q->whereNull('company_id')->orWhereIn('company_id', $managerCompanyIds));
            } else {
                $query->forCompany($authUser->company_id);
            }
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('date', 'like', $like);
            });

            $targetUrl = $isPortal && Route::has('portal.holidays.index')
                ? route('portal.holidays.index', ['q' => $term])
                : (Route::has('employee.dashboard') ? route('employee.dashboard') : route('portal.dashboard'));

            foreach ($query->orderBy('date', 'desc')->limit(3)->get() as $h) {
                $date = '';
                if ($h->date) {
                    try {
                        $date = Carbon::parse($h->date)->format('d M Y');
                    } catch (\Throwable) {
                        $date = (string) $h->date;
                    }
                }
                $desc = trim($date . ($h->description ? ' · ' . Str::limit($h->description, 35) : ''));
                $records[] = $this->record(
                    'holiday-' . $h->id,
                    $h->name,
                    $desc,
                    __('common.holidays'),
                    $targetUrl,
                    'fas fa-calendar-alt'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Roles (Admin & privileged).
     */
    protected function searchRoles(string $term, User $authUser): array
    {
        $records = [];
        if (! $authUser->hasRole('admin') && ! $authUser->can('role-read')) {
            return [];
        }

        try {
            $roles = Role::where('name', 'like', '%' . $term . '%')->limit(3)->get();
            foreach ($roles as $r) {
                $records[] = $this->record(
                    'role-' . $r->id,
                    $r->name,
                    __('common.roles') . ' & Permissions',
                    __('common.roles'),
                    route('portal.roles.index', ['q' => $r->name]),
                    'fas fa-user-shield'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Import Jobs (Admin & Manager).
     */
    protected function searchImportJobs(string $term, User $authUser): array
    {
        $records = [];
        if (! $authUser->hasRole('admin') && ! $authUser->hasRole('manager') && ! $authUser->can('import_job-read')) {
            return [];
        }

        try {
            $query = $authUser->hasRole('admin') ? ImportJob::query() : ImportJob::forUser($authUser->id);
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('file_name', 'like', $like)
                  ->orWhere('import_type', 'like', $like)
                  ->orWhere('status', 'like', $like);
            });
            foreach ($query->latest('id')->limit(3)->get() as $ij) {
                $label = ($ij->file_name ?: ucfirst($ij->import_type)) . ($ij->status ? ' (' . ucfirst($ij->status) . ')' : '');
                $meta = [];
                if ($ij->import_type) {
                    $meta[] = ucfirst($ij->import_type);
                }
                if ($ij->total_rows) {
                    $meta[] = $ij->total_rows . ' rows';
                }
                if ($ij->created_at) {
                    $meta[] = $ij->created_at->format('d/m/Y');
                }
                $records[] = $this->record(
                    'import-job-' . $ij->id,
                    $label,
                    implode(' · ', $meta),
                    __('common.import_jobs'),
                    route('portal.import-jobs.index', ['q' => $ij->file_name ?: $ij->import_type]),
                    'fas fa-file-import'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Download Jobs (Admin, Manager, Supervisor).
     */
    protected function searchDownloadJobs(string $term, User $authUser): array
    {
        $records = [];
        if (! $authUser->hasAnyRole(['admin', 'manager', 'supervisor'])) {
            return [];
        }

        try {
            $query = $authUser->hasRole('admin') ? DownloadJob::query() : DownloadJob::where('user_id', $authUser->id);
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('file_name', 'like', $like)
                  ->orWhere('job_type', 'like', $like)
                  ->orWhere('status', 'like', $like)
                  ->orWhere('uuid', 'like', $like);
            });
            foreach ($query->latest('id')->limit(3)->get() as $dj) {
                $label = ($dj->file_name ?: ucfirst($dj->job_type)) . ($dj->status ? ' (' . ucfirst($dj->status) . ')' : '');
                $meta = [];
                if ($dj->report_format) {
                    $meta[] = strtoupper($dj->report_format);
                }
                if ($dj->total_records) {
                    $meta[] = $dj->total_records . ' records';
                }
                if ($dj->created_at) {
                    $meta[] = $dj->created_at->format('d/m/Y');
                }
                $records[] = $this->record(
                    'download-job-' . $dj->id,
                    $label,
                    implode(' · ', $meta),
                    __('common.download_jobs'),
                    route('portal.download-jobs.index', ['q' => $dj->file_name ?: $dj->job_type]),
                    'fas fa-file-export'
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    /**
     * Search Audit Logs (Portal & Employee).
     */
    protected function searchAuditLogs(string $term, User $authUser, bool $isPortal): array
    {
        $records = [];
        try {
            if ($isPortal) {
                if ($authUser->hasRole('admin') || $authUser->can('audit_log-read_all') || $authUser->can('audit_log-read_own_only')) {
                    $logs = AuditLog::search($term)->latest('id')->limit(3)->get();
                    foreach ($logs as $log) {
                        $label = ucwords(str_replace('_', ' ', $log->action_type ?? 'Action')) . ($log->user ? ' by ' . $log->user : '');
                        $desc = trim(($log->action_perform ? Str::limit($log->action_perform, 40) : '') . ($log->created_at ? ' · ' . $log->created_at->format('d/m/Y H:i') : ''));
                        $records[] = $this->record(
                            'audit-log-' . $log->id,
                            $label,
                            $desc,
                            __('common.audit_logs'),
                            route('portal.auditlogs.index', ['q' => $term]),
                            'fas fa-history'
                        );
                    }
                }
            } else {
                $logs = AuditLog::where('user_id', $authUser->id)->search($term)->latest('id')->limit(3)->get();
                foreach ($logs as $log) {
                    $label = ucwords(str_replace('_', ' ', $log->action_type ?? 'Action'));
                    $desc = trim(($log->action_perform ? Str::limit($log->action_perform, 40) : '') . ($log->created_at ? ' · ' . $log->created_at->format('d/m/Y H:i') : ''));
                    $records[] = $this->record(
                        'employee-audit-log-' . $log->id,
                        $label,
                        $desc,
                        __('common.audit_logs'),
                        route('employee.auditlogs', ['q' => $term]),
                        'fas fa-history'
                    );
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $records;
    }

    private function record(string $id, string $label, string $description, string $group, string $url, string $icon): array
    {
        return compact('id', 'label', 'description', 'group', 'url', 'icon') + ['search_terms' => []];
    }

    private function calculateSearchScore(array $item, string $searchTerm): int
    {
        $score = 0;

        // Check exact matches in label (highest score)
        if (str_contains(strtolower($item['label']), $searchTerm)) {
            $score += 100;

            // Bonus for exact match at start
            if (str_starts_with(strtolower($item['label']), $searchTerm)) {
                $score += 50;
            }
        }

        // Check description
        if (str_contains(strtolower($item['description']), $searchTerm)) {
            $score += 30;
        }

        // Check group
        if (str_contains(strtolower($item['group']), $searchTerm)) {
            $score += 20;
        }

        // Check search terms/keywords
        foreach ($item['search_terms'] as $term) {
            if (str_contains(strtolower($term), $searchTerm)) {
                $score += 10;

                // Bonus for exact keyword match
                if (strtolower($term) === $searchTerm) {
                    $score += 40;
                }
            }
        }

        return $score;
    }

    public function render()
    {
        return view('livewire.components.global-search');
    }
}
