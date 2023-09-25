<?php

namespace App\Console\Commands;

use App\Mail\SendPayslip;
use mikehaertl\pdftk\Pdf;
use Illuminate\Console\Command;
use Escarter\PopplerPhp\PdfToText;
use Escarter\PopplerPhp\PdfSeparate;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;


class SplitPdfCommand extends Command
{

    protected $employees  = [
        [
            'email'=> 'njokem@gmail.com',
            'matricule' => '135287',
            'password' => 'd620f6375f'
        ],
        [
            'email'=> 'njokem@gmail.com',
            'matricule' => '138163',
            'password' => 'd620f6375f',
          
        ],
        [
            'email'=> 'brienom@gmail.com',
            'matricule' => 'ENY045',
            'password' => '5378ff3b40',
        ],
        [
            'email'=> 'brienom@gmail.com',
            'matricule' => '135260',
            'password' => '5378ff3b40',
        ],
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'split:pdf-file';

    /**
     * The console Split Pdf file to single pages.
     *
     * @var string
     */
    protected $description = 'Split Pdf file to single pages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Splitting file');
        $path = Storage::disk('public');

        collect(Storage::disk('splitted')->files())->each(function($file) {
                Storage::disk('splitted')->delete($file);
        });
        // dd(Storage::disk('splitted')->files());
        
        $file = $path->path('DOC-20230305-WA0063.4743878545745603187.pdf');

        // $script = ["/opt/homebrew/bin/pdfseparate",$file, 'page-%d.pdf'];
        $process = PdfSeparate::getOutput($file, '/opt/homebrew/bin/pdfseparate',Storage::disk('splitted')->path('page_%d.pdf'));

        $files =  Storage::disk('splitted')->files();

        // dd($files);


        foreach ($files as $file) {
           
            // dd(strpos(PdfToText::getText($from_path, '/opt/homebrew/bin/pdftotext'), 'Matricule 135121') !== FALSE);
            collect($this->employees)->each(function($employee) use($file){
                $from_path = Storage::disk('splitted')->path($file);

                $pdf_text = PdfToText::getText($from_path, '/opt/homebrew/bin/pdftotext');

                preg_match("/\b" . $employee['matricule'] . "\b/i", $pdf_text, $matches);

                // dd(Storage::disk('splitted')->exists($file));
                
                if(!empty($matches) && $matches[0] === $employee['matricule']){
     
                    if(Storage::disk('splitted')->exists($file)){
                        //  Storage::disk('modified')->put($employee['matricule'].'.pdf', Storage::disk('splitted')->get($file));


                        $pdf = new Pdf(Storage::disk('splitted')->path($file));

                      
                        $result = $pdf->allow('AllFeatures')->setUserPassword($employee['password'])          
                            ->passwordEncryption(128)   
                            ->saveAs(Storage::disk('modified')->path($employee['matricule'] . '_encrypted.pdf'));

                        if ($result === false) {
                            dd($pdf->getError());
                        }

                        // Mail::to($employee['email'])->send(new SendPayslip($employee['email']));
                        // Storage::disk('splitted')->delete($file);
                    }
                }
            });
        }
    }
}
