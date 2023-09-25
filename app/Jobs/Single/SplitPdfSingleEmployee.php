<?php

namespace App\Jobs\Single;

use Illuminate\Bus\Queueable;
use Escarter\PopplerPhp\PdfSeparate;
use Illuminate\Support\Facades\File;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SplitPdfSingleEmployee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $file;
    protected $destination;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file, $destination)
    {
        $this->file = $file;
        $this->destination = $destination;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $file = Storage::disk('raw')->path($this->path);
        Storage::disk('splitted')->makeDirectory($this->destination);

        if (!File::exists($this->file)) {
            return;
        }

        PdfSeparate::getOutput($this->file, config('ciblerh.pdftsepare_path'), Storage::disk('splitted')->path($this->destination . '/page_%d.pdf'));
   
    }
}
