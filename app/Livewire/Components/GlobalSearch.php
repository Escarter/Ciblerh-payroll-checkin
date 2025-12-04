<?php

namespace App\Livewire\Components;

use App\Utils\PermissionHelper;
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

        // Limit results
        $this->results = array_slice($filtered, 0, 20);
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