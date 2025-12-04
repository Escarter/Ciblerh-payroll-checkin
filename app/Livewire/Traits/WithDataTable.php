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
        // Dispatch toast notification
        $this->dispatch("showToast", message: $message, type: "success");
        // Close the modal
        $this->dispatch("close-modal", id: $modal);
    }

    public function showToast($message, $type = 'success')
    {
        $this->dispatch("showToast", message: $message, type: $type);
    }

    public function updatingQuery()
    {
        $this->resetPage();
    }
 
}