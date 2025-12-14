<?php

namespace App\Jobs;

use App\Models\SendPayslipProcess;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Escarter\PopplerPhp\PdfSeparate;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\File;

class SplitPdfJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue connection name
     */
    public $queue = 'pdf-processing';

    protected $process;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(SendPayslipProcess $process)
    {
        $this->process = $process;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $file = Storage::disk('raw')->path($this->path);
        Storage::disk('splitted')->makeDirectory($this->process->destination_directory);

        if(!File::exists($this->process->raw_file)){
            return;
        }
        
        PdfSeparate::getOutput($this->process->raw_file, config('ciblerh.pdftsepare_path'), Storage::disk('splitted')->path($this->process->destination_directory . '/page_%d.pdf'));
        // PdfSeparate::getOutput($this->process->raw_file, '/usr/local/bin/pdfseparate', Storage::disk('splitted')->path($this->process->destination_directory . '/page_%d.pdf'));
    }
}
