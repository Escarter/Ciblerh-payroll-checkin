<?php

use App\Jobs\RenameEncryptPdfJob;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use App\Models\Department;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Bus\Batch;
use Escarter\PopplerPhp\PdfToText;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('splitted');
    Storage::fake('modified');
    
    Config::set('ciblerh.pdftotext_path', '/usr/local/bin/pdftotext');
    Config::set('ciblerh.pdftk_path', '/usr/local/bin/pdftk');
    Config::set('ciblerh.temp_dir', sys_get_temp_dir());
    
    Setting::factory()->create();
});

test('it skips processing when batch is cancelled', function () {
    $department = Department::factory()->create();
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
    ]);
    $chunk = collect(['page_1.pdf']);
    
    $batch = \Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(true);
    
    $job = \Mockery::mock(RenameEncryptPdfJob::class, [$chunk, $process->id])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    // No payslips should be created
    expect(Payslip::count())->toBe(0);
});

test('it creates failed payslip when employee matricule is empty', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'matricule' => '',
        'company_id' => $department->company_id,
        'department_id' => $department->id,
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'user_id' => $user->id,
    ]);
    
    $chunk = collect(['page_1.pdf']);
    $filePath = $process->destination_directory . '/page_1.pdf';
    Storage::disk('splitted')->put($filePath, 'fake pdf content');
    
    // Mock PdfToText static method
    \Mockery::mock('alias:' . PdfToText::class)
        ->shouldReceive('getText')
        ->andReturn('some text');
    
    $batch = \Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = \Mockery::mock(RenameEncryptPdfJob::class, [$chunk, $process->id])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    $payslip = Payslip::where('employee_id', $user->id)->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->encryption_status)->toBe(Payslip::STATUS_FAILED);
    expect($payslip->failure_reason)->toContain('Matricule is empty');
});

test('it encrypts PDF when employee matricule is found', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'matricule' => 'EMP001',
        'pdf_password' => 'test123',
        'company_id' => $department->company_id,
        'department_id' => $department->id,
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'user_id' => $user->id,
    ]);
    
    $filePath = $process->destination_directory . '/page_1.pdf';
    $chunk = collect([$filePath]);
    Storage::disk('splitted')->put($filePath, 'fake pdf content');
    
    // Mock PdfToText to return text containing the matricule
    \Mockery::mock('alias:' . PdfToText::class)
        ->shouldReceive('getText')
        ->andReturn('Matricule EMP001');
    
    // Mock Pdf encryption - create a fake encrypted file
    $destinationFile = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    Storage::disk('modified')->put($destinationFile, 'encrypted pdf content');
    
    $batch = \Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = \Mockery::mock(RenameEncryptPdfJob::class, [$chunk, $process->id])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    
    // Mock the Pdf class for encryption
    $pdfMock = \Mockery::mock('overload:mikehaertl\pdftk\Pdf');
    $pdfMock->shouldReceive('__construct')->andReturnSelf();
    $pdfMock->tempDir = sys_get_temp_dir();
    $pdfMock->shouldReceive('setUserPassword')->andReturnSelf();
    $pdfMock->shouldReceive('passwordEncryption')->andReturnSelf();
    $pdfMock->shouldReceive('saveAs')->andReturn(true);
    
    $job->handle();
    
    $payslip = Payslip::where('employee_id', $user->id)->first();
    
    expect($payslip)->not->toBeNull();
    expect($payslip->file)->toContain($user->matricule);
});

test('it combines multiple PDF files for same employee', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'matricule' => 'EMP002',
        'pdf_password' => 'test123',
        'company_id' => $department->company_id,
        'department_id' => $department->id,
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'user_id' => $user->id,
    ]);
    
    // Create first payslip record
    $firstFile = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    $payslip = Payslip::create([
        'employee_id' => $user->id,
        'send_payslip_process_id' => $process->id,
        'month' => $process->month,
        'year' => now()->year,
        'file' => $firstFile,
        'encryption_status' => Payslip::STATUS_SUCCESSFUL,
        'company_id' => $user->company_id,
        'department_id' => $user->department_id,
        'service_id' => $user->service_id,
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'phone' => $user->professional_phone_number ?? $user->personal_phone_number,
        'matricule' => $user->matricule,
    ]);
    
    Storage::disk('modified')->put($firstFile, 'fake encrypted pdf');
    
    // Second file with same matricule
    $secondFilePath = $process->destination_directory . '/page_2.pdf';
    $chunk = collect([$secondFilePath]);
    Storage::disk('splitted')->put($secondFilePath, 'fake pdf content');
    
    // Mock PdfToText to return text containing the matricule
    \Mockery::mock('alias:' . PdfToText::class)
        ->shouldReceive('getText')
        ->andReturn('Matricule EMP002');
    
    // Mock Pdf combination
    $pdfMock = \Mockery::mock('overload:mikehaertl\pdftk\Pdf');
    $pdfMock->shouldReceive('__construct')->andReturnSelf();
    $pdfMock->tempDir = sys_get_temp_dir();
    $pdfMock->shouldReceive('saveAs')->andReturn(true);
    
    // Mock second Pdf for encryption
    $pdfEncryptMock = \Mockery::mock('overload:mikehaertl\pdftk\Pdf');
    $pdfEncryptMock->shouldReceive('__construct')->andReturnSelf();
    $pdfEncryptMock->tempDir = sys_get_temp_dir();
    $pdfEncryptMock->shouldReceive('setUserPassword')->andReturnSelf();
    $pdfEncryptMock->shouldReceive('passwordEncryption')->andReturnSelf();
    $pdfEncryptMock->shouldReceive('saveAs')->andReturn(true);
    
    $destinationFile = $process->destination_directory . '/' . $user->matricule . '_' . $process->month . '.pdf';
    Storage::disk('modified')->put($destinationFile, 'combined encrypted pdf');
    
    $batch = \Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = \Mockery::mock(RenameEncryptPdfJob::class, [$chunk, $process->id])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    $payslip->refresh();
    
    expect($payslip->encryption_status)->toBe(Payslip::STATUS_SUCCESSFUL);
    expect($payslip->file)->toContain($user->matricule);
});

test('it skips file when employee matricule not found in PDF', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'matricule' => 'EMP003',
        'company_id' => $department->company_id,
        'department_id' => $department->id,
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'user_id' => $user->id,
    ]);
    
    $filePath = $process->destination_directory . '/page_1.pdf';
    $chunk = collect([$filePath]);
    Storage::disk('splitted')->put($filePath, 'fake pdf content');
    
    // Mock PdfToText to return text without the matricule
    \Mockery::mock('alias:' . PdfToText::class)
        ->shouldReceive('getText')
        ->andReturn('Some other text without EMP003');
    
    // Mock Pdf class in case it's called (shouldn't be, but mock it to prevent errors)
    $pdfMock = \Mockery::mock('overload:mikehaertl\pdftk\Pdf');
    $pdfMock->shouldReceive('__construct')->andReturnSelf();
    $pdfMock->tempDir = sys_get_temp_dir();
    $pdfMock->shouldReceive('setUserPassword')->andReturnSelf();
    $pdfMock->shouldReceive('passwordEncryption')->andReturnSelf();
    $pdfMock->shouldReceive('saveAs')->andReturn(true);
    
    $batch = \Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = \Mockery::mock(RenameEncryptPdfJob::class, [$chunk, $process->id])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    // No payslip should be created for this employee
    $payslip = Payslip::where('employee_id', $user->id)->first();
    expect($payslip)->toBeNull();
});

test('it handles PDF encryption failure gracefully', function () {
    $department = Department::factory()->create();
    $user = User::factory()->create([
        'matricule' => 'EMP004',
        'pdf_password' => 'test123',
        'company_id' => $department->company_id,
        'department_id' => $department->id,
    ]);
    
    $process = SendPayslipProcess::factory()->create([
        'department_id' => $department->id,
        'user_id' => $user->id,
    ]);
    
    $filePath = $process->destination_directory . '/page_1.pdf';
    $chunk = collect([$filePath]);
    Storage::disk('splitted')->put($filePath, 'fake pdf content');
    
    // Mock PdfToText to return text containing the matricule
    \Mockery::mock('alias:' . PdfToText::class)
        ->shouldReceive('getText')
        ->andReturn('Matricule EMP004');
    
    // Mock Pdf encryption to fail (saveAs returns false)
    $pdfMock = \Mockery::mock('overload:mikehaertl\pdftk\Pdf');
    $pdfMock->shouldReceive('__construct')->andReturnSelf();
    $pdfMock->tempDir = sys_get_temp_dir();
    $pdfMock->shouldReceive('setUserPassword')->andReturnSelf();
    $pdfMock->shouldReceive('passwordEncryption')->andReturnSelf();
    $pdfMock->shouldReceive('saveAs')->andReturn(false); // Encryption fails
    
    $batch = \Mockery::mock(Batch::class);
    $batch->shouldReceive('cancelled')->andReturn(false);
    
    $job = \Mockery::mock(RenameEncryptPdfJob::class, [$chunk, $process->id])->makePartial();
    $job->shouldReceive('batch')->andReturn($batch);
    $job->handle();
    
    // Payslip should not be created if encryption fails
    $payslip = Payslip::where('employee_id', $user->id)->first();
    expect($payslip)->toBeNull();
});


afterEach(function () {
    \Mockery::close();
});

