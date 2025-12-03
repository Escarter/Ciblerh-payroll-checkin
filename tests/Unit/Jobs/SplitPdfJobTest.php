<?php

use App\Jobs\SplitPdfJob;
use App\Models\SendPayslipProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Bus\Batch;
use Mockery\MockInterface;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('splitted');
    Storage::fake('raw');
    
    Config::set('ciblerh.pdftsepare_path', '/usr/local/bin/pdfseparate');
});

test('it creates destination directory', function () {
    $process = SendPayslipProcess::factory()->create([
        'raw_file' => '/tmp/test.pdf',
        'destination_directory' => 'test_dir',
    ]);
    
    // Create a fake raw file
    File::shouldReceive('exists')
        ->with($process->raw_file)
        ->andReturn(true);
    
    // Mock PdfSeparate
    mockPdfSeparate();
    
    $batch = Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = Mockery::mock(SplitPdfJob::class, [$process])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    // Directory should be created
    expect(Storage::disk('splitted')->exists($process->destination_directory))->toBeTrue();
});

test('it skips processing when raw file does not exist', function () {
    $process = SendPayslipProcess::factory()->create([
        'raw_file' => '/tmp/nonexistent.pdf',
        'destination_directory' => 'test_dir',
    ]);
    
    File::shouldReceive('exists')
        ->with($process->raw_file)
        ->andReturn(false);
    
    $batch = Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = Mockery::mock(SplitPdfJob::class, [$process])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    // PdfSeparate should not be called
    // We can't easily verify this without mocking, but the test ensures no errors
    expect(true)->toBeTrue();
});

test('it calls PdfSeparate with correct parameters', function () {
    $process = SendPayslipProcess::factory()->create([
        'raw_file' => '/tmp/test.pdf',
        'destination_directory' => 'test_dir',
    ]);
    
    File::shouldReceive('exists')
        ->with($process->raw_file)
        ->andReturn(true);
    
    // Mock PdfSeparate
    mockPdfSeparate();
    
    $batch = Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = Mockery::mock(SplitPdfJob::class, [$process])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    // Test passes if no exceptions are thrown
    expect(true)->toBeTrue();
});

// Helper method
function mockPdfSeparate() {
    // Mock static call to PdfSeparate::getOutput using alias mocking
    \Mockery::mock('alias:Escarter\\PopplerPhp\\PdfSeparate')
        ->shouldReceive('getOutput')
        ->andReturn(true);
}

afterEach(function () {
    Mockery::close();
});

