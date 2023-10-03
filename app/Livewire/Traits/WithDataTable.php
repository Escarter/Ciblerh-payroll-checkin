<?php 

namespace App\Livewire\Traits;

use Livewire\WithPagination;
use Livewire\WithFileUploads;

trait WithDataTable {
    use WithPagination, WithFileUploads;

    //DataTable props
    public ?string $query = null;
    public ?string $resultCount;
    public string $orderBy = 'created_at';
    public string $orderAsc = 'desc';
    public int $perPage = 15;

    protected $paginationTheme = "bootstrap";

    public function closeModalAndFlashMessage($message, $modal)  
    {
        $this->dispatch('cancel', modalId: $modal);
        session()->flash('message', $message);
    }

    public function updatingQuery()
    {
        $this->resetPage();
    }
 
}