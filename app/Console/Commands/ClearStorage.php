<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ClearStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wima:clear-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // dd(Storage::disk('splitted')->allDirectories());

        collect(Storage::disk('splitted')->allDirectories())->each(function ($file) {
            Storage::disk('splitted')->deleteDirectory($file);
        });
        collect(Storage::disk('modified')->allDirectories())->each(function ($file) {
            Storage::disk('modified')->deleteDirectory($file);
        });
        collect(Storage::disk('raw')->allDirectories())->each(function ($file) {
            Storage::disk('raw')->deleteDirectory($file);
        });
    }
}
