<?php

namespace App\Console\Commands;

use App\Models\SendPayslipProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearSuccessfulJobsFolders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wima:clean-processed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete folders of all successfully processed jobs';

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
        $successful_jobs  = SendPayslipProcess::where('status','successful')->get();

        foreach ($successful_jobs as $job) {
            
           Storage::disk('splitted')->deleteDirectory($job->destination_directory);
           Storage::disk('modified')->deleteDirectory($job->destination_directory);

           if(File::exists($job->raw_file)){
               File::delete($job->raw_file);
           }
        }
    }
}
