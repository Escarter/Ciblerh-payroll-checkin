<?php

namespace App\Livewire\Components;

use App\Utils\PermissionHelper;
use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use App\Models\User;
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
        
        if (empty(trim($this->search))) {
            // Show all accessible items when no search term
            $this->results = array_slice($allItems, 0, 20);
            return;
        }

        $searchTerm = strtolower(trim($this->search));
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

        $this->results = array_merge(array_slice($filtered, 0, 12), $this->searchRecords(trim($this->search)));
    }

    // Records the user may open, searched across models and linked to the page that lists them.
    private function searchRecords(string $term): array
    {
        $user = auth()->user();

        if (mb_strlen($term) < 2 || ! $user || ! $user->hasAnyRole(['admin', 'manager', 'supervisor'])) {
            return [];
        }

        $isSupervisor = $user->hasRole('supervisor') && ! $user->hasAnyRole(['admin', 'manager']);
        $like = '%' . $term . '%';
        $records = [];

        try {
            User::search($term)->with('company')->limit(6)->get()->each(function ($u) use (&$records) {
                $records[] = $this->record('user-' . $u->id, $u->name, trim(($u->email ?: $u->matricule ?: '') . ' ' . ($u->company?->name ? '· ' . $u->company->name : '')),
                    __('common.employees'), route('portal.all-employees', ['q' => $u->matricule ?: ($u->email ?: $u->last_name)]), 'fas fa-user');
            });

            if (! $isSupervisor) {
                Company::manager()->where('name', 'like', $like)->limit(4)->get()->each(function ($c) use (&$records) {
                    $records[] = $this->record('company-' . $c->id, $c->name, '', __('common.companies'), route('portal.companies.index', ['q' => $c->name]), 'fas fa-building');
                });
            }

            $departments = Department::query()->with('company');
            $isSupervisor ? $departments->supervisor() : $departments->manager();
            $departments->where('name', 'like', $like)->limit(4)->get()->each(function ($d) use (&$records, $isSupervisor) {
                $url = $isSupervisor
                    ? route('portal.departments.supervisor', ['q' => $d->name])
                    : ($d->company ? route('portal.departments.index', ['company_uuid' => $d->company->uuid, 'q' => $d->name]) : null);
                if ($url) {
                    $records[] = $this->record('department-' . $d->id, $d->name, $d->company?->name ?? '', __('common.departments'), $url, 'fas fa-sitemap');
                }
            });

            $services = Service::query()->with('department');
            $isSupervisor ? $services->supervisor() : $services->manager();
            $services->where('name', 'like', $like)->limit(4)->get()->each(function ($s) use (&$records) {
                if ($s->department) {
                    $records[] = $this->record('service-' . $s->id, $s->name, $s->department->name, __('common.services'),
                        route('portal.services.index', ['department_uuid' => $s->department->uuid, 'q' => $s->name]), 'fas fa-briefcase');
                }
            });
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