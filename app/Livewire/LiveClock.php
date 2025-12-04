<?php

namespace App\Livewire;

use Livewire\Component;

class LiveClock extends Component
{
    public $currentTime;
    public $currentDate;

    public function mount()
    {
        $this->updateTime();
    }

    public function updateTime()
    {
        $this->currentTime = now()->format('H:i:s');
        $this->currentDate = now()->format(__('dashboard.date_format_readable'));
    }

    public function render()
    {
        return view('livewire.live-clock');
    }
}
