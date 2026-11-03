<?php

namespace Tests\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Batch;
use Mockery;

class TestHelpers
{
    /**
     * Mock Mail facade to simulate failures
     */
    public static function mockMailWithFailures(array $failedEmails = [])
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andReturn(true);
    }

    /**
     * Mock Mail facade to simulate success
     */
    public static function mockMailSuccess()
    {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('send')->andReturn(true);
    }

    /**
     * Mock batch for jobs
     */
    public static function mockBatch($cancelled = false)
    {
        $batch = Mockery::mock(Batch::class);
        $batch->shouldReceive('cancelled')->andReturn($cancelled);
        return $batch;
    }
}


















