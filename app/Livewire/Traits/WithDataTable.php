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
        session()->flash(
            str_replace("\\App\\Livewire\\", "", get_class($this)) . "." . $modal,
            $message
        );
        // Generic event expected by tests
        $this->dispatch("flash-message", message: $message, modalId: $modal);
        // Specific event variant for consumers that use modal-specific channels
        $this->dispatch("flash-message-{$modal}", message: $message, modalId: $modal);
    }

    public function updatingQuery()
    {
        $this->resetPage();
    }
 
}