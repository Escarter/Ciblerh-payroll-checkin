<?php

use App\Models\AnnualDeclaration;
use App\Models\Company;
use App\Models\SendAnnualDeclarationProcess;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
    Gate::before(fn () => true);
    $this->user = User::factory()->create();
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->user->syncRoles(['admin']);
    $this->actingAs($this->user);
});

function makeProcess(User $user): SendAnnualDeclarationProcess
{
    $company = Company::factory()->create();

    return SendAnnualDeclarationProcess::create([
        'user_id' => $user->id, 'company_id' => $company->id, 'year' => 2024,
        'status' => 'successful', 'destination_directory' => 'x', 'total_pages' => 3,
    ]);
}

test('details renders every tab', function () {
    $process = makeProcess($this->user);
    AnnualDeclaration::create([
        'send_annual_declaration_process_id' => $process->id, 'company_id' => $process->company_id,
        'page_number' => 1, 'page_status' => 'empty', 'matricule' => 'EM1', 'year' => 2024, 'failure_reason' => 'n',
    ]);

    Livewire::test(\App\Livewire\Portal\AnnualDeclarations\Details::class, ['id' => $process->id])
        ->assertOk()
        ->call('switchTab', 'review')
        ->assertSee('EM1')
        ->call('switchTab', 'failed')
        ->assertOk();
});

test('employee list renders', function () {
    Livewire::test(\App\Livewire\Employee\AnnualDeclarations\Index::class)->assertOk();
});

test('index renders stats and process rows', function () {
    $process = makeProcess($this->user);
    makeProcess($this->user)->update(['status' => 'processing', 'percentage_completion' => 40]);

    Livewire::test(\App\Livewire\Portal\AnnualDeclarations\Index::class)
        ->assertOk()
        ->assertSee(__('annual_declarations.history'))
        ->assertSee('2024');
});

test('monthly payslip index renders with status cards', function () {
    \App\Models\SendPayslipProcess::factory()->create(['status' => 'successful']);

    Livewire::test(\App\Livewire\Portal\Payslips\Index::class)
        ->assertOk()
        ->assertSee(__('common.successful'))
        ->assertSeeHtml('id="select-all-payslips"');
});
