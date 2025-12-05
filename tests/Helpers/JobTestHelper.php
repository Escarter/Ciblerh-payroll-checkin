<?php

namespace Tests\Helpers;

use Illuminate\Bus\Batch;
use Mockery;

class JobTestHelper
{
    /**
     * Create a mock batch for job testing
     */
    public static function mockBatch($cancelled = false): Batch
    {
        $batch = Mockery::mock(Batch::class);
        $batch->shouldReceive('cancelled')->andReturn($cancelled);
        return $batch;
    }

    /**
     * Create a job with mocked batch
     */
    public static function createJobWithBatch($jobClass, $args, $cancelled = false)
    {
        $batch = self::mockBatch($cancelled);
        $job = Mockery::mock($jobClass, $args)->makePartial();
        $job->shouldReceive('batch')->andReturn($batch);
        return $job;
    }
}









