<?php

use App\Models\SendPayslipProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    \Spatie\Permission\Models\Role::findOrCreate('employee', 'web');
});

test('payslips diagnose command runs pdf tool checks', function () {
    Config::set('ciblerh.pdftotext_path', PHP_BINARY);
    Config::set('ciblerh.pdftsepare_path', PHP_BINARY);
    Config::set('ciblerh.pdftk_path', PHP_BINARY);

    $this->artisan('payslips:diagnose')
        ->assertExitCode(0);
});

test('payslips diagnose command fails when process not found', function () {
    $this->artisan('payslips:diagnose', ['--process' => 999999])
        ->assertExitCode(1);
});

test('payslips diagnose command reports process metadata', function () {
    Config::set('ciblerh.pdftotext_path', PHP_BINARY);
    Config::set('ciblerh.pdftsepare_path', PHP_BINARY);
    Config::set('ciblerh.pdftk_path', PHP_BINARY);

    $process = SendPayslipProcess::factory()->create();

    $this->artisan('payslips:diagnose', ['--process' => $process->id])
        ->expectsOutputToContain('Employee pool size:')
        ->expectsOutputToContain('Company:')
        ->assertExitCode(1); // fails when no splitted pages exist for the process
});
