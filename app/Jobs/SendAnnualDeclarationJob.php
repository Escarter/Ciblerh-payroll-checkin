<?php

namespace App\Jobs;

use App\Models\AnnualDeclaration;
use App\Services\AnnualDeclarationNotifier;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnualDeclarationJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(protected array $declarationIds, protected bool $force = false)
    {
        $this->onQueue('emails');
    }

    public function handle(AnnualDeclarationNotifier $notifier): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        AnnualDeclaration::whereIn('id', $this->declarationIds)->get()
            ->each(fn (AnnualDeclaration $declaration) => $notifier->send($declaration, $this->force));
    }
}
