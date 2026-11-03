<?php

namespace App\Jobs;

use App\Models\SendAnnualDeclarationProcess;
use Escarter\PopplerPhp\PdfSeparate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SplitAnnualDeclarationPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(protected SendAnnualDeclarationProcess $process)
    {
        $this->onQueue('pdf-processing');
    }

    public function handle(): void
    {
        Storage::disk('splitted')->makeDirectory($this->process->destination_directory);

        if (!File::exists($this->process->raw_file)) {
            throw new \RuntimeException('Raw PDF file not found on disk: ' . $this->process->raw_file);
        }

        PdfSeparate::getOutput(
            $this->process->raw_file,
            config('ciblerh.pdftsepare_path'),
            Storage::disk('splitted')->path($this->process->destination_directory . '/page_%d.pdf')
        );
    }
}
