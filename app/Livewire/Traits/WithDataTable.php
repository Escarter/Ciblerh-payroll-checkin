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
            // Use a unique key for flash messages to avoid conflicts
            // and allow tests to assert on specific events
            str_replace("\\App\\Livewire\\", "", get_class($this)) . "." . $modal,
            $message
        );
        $this->dispatch("flash-message-{$modal}", message: $message, modalId: $modal);
    }

    public function updatingQuery()
    {
        $this->resetPage();
    }
 
}