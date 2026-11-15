<?php

use App\Jobs\ProcessAnnualDeclarationPagesJob;
use App\Mail\SendAnnualDeclaration;
use App\Models\AnnualDeclaration;
use App\Models\Company;
use App\Models\Department;
use App\Models\SendAnnualDeclarationProcess;
use App\Models\User;
use App\Services\AnnualDeclarationNotifier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

function pageText(string $matricule, string $amount, int $year = 2024): string
{
    return "EMPLOI SERVICE - DECLARATION INDIVIDUELLE DES REVENUS SALARIAUX - {$year}\n\n"
        . "Nom :    DOE                    {$matricule}            NUI :\n\n"
        . "     1- Montants payés en espèces ou crédités en compte au cours de la période : {$amount}\n";
}

beforeEach(function () {
    Storage::fake('splitted');
    Storage::fake('modified');
    Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

    $this->company = Company::factory()->create();
    $this->department = Department::factory()->create(['company_id' => $this->company->id]);
    $this->process = SendAnnualDeclarationProcess::create([
        'user_id' => User::factory()->create()->id,
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'year' => 2024,
        'status' => 'processing',
        'destination_directory' => 'annual_test',
    ]);
});

test('page job records unmatched, year mismatch and empty pages for review', function () {
    $employee = User::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'matricule' => 'EM1001',
    ]);

    $parser = Mockery::mock(\App\Services\AnnualDeclarationParser::class)->makePartial();
    $texts = [
        'p/page_1.pdf' => pageText('EM9999', '1 000'),
        'p/page_2.pdf' => pageText('EM1001', '1 000', 2023),
        'p/page_3.pdf' => pageText('EM1001', ''),
        'p/page_4.pdf' => 'blank',
    ];
    $parser->shouldReceive('parseFile')->andReturnUsing(function ($path) use ($parser, $texts) {
        foreach ($texts as $file => $text) {
            if (str_ends_with($path, basename($file))) {
                return $parser->parseText($text);
            }
        }
    });

    foreach (array_keys($texts) as $f) {
        Storage::disk('splitted')->put($f, 'x');
    }

    (new ProcessAnnualDeclarationPagesJob(collect(array_keys($texts)), $this->process->id))->handle($parser);

    $rows = AnnualDeclaration::orderBy('page_number')->get();
    expect($rows)->toHaveCount(4)
        ->and($rows->pluck('page_status')->all())->toBe(['unmatched', 'unmatched', 'empty', 'unmatched'])
        ->and($rows->every(fn ($r) => $r->failure_reason !== null))->toBeTrue()
        ->and($rows[2]->employee_id)->toBe($employee->id);
});

test('notifier emails the encrypted declaration and is idempotent', function () {
    Mail::fake();
    $employee = User::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'receive_sms_notifications' => false,
    ]);
    Storage::disk('modified')->put('annual_test/EM1_2024.pdf', 'pdf');

    $declaration = AnnualDeclaration::create([
        'send_annual_declaration_process_id' => $this->process->id,
        'employee_id' => $employee->id,
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'page_number' => 1,
        'page_status' => AnnualDeclaration::PAGE_MATCHED,
        'matricule' => 'EM1',
        'year' => 2024,
        'file' => 'annual_test/EM1_2024.pdf',
        'encryption_status' => AnnualDeclaration::STATUS_SUCCESSFUL,
    ]);

    app(AnnualDeclarationNotifier::class)->send($declaration);
    app(AnnualDeclarationNotifier::class)->send($declaration->fresh());

    Mail::assertSent(SendAnnualDeclaration::class, 1);
    expect($declaration->fresh()->email_sent_status)->toBe(AnnualDeclaration::STATUS_SUCCESSFUL);
});

test('notifier fails email when the encrypted file is missing', function () {
    Mail::fake();
    $employee = User::factory()->create(['company_id' => $this->company->id, 'department_id' => $this->department->id]);

    $declaration = AnnualDeclaration::create([
        'send_annual_declaration_process_id' => $this->process->id,
        'employee_id' => $employee->id,
        'company_id' => $this->company->id,
        'page_number' => 1,
        'page_status' => AnnualDeclaration::PAGE_MATCHED,
        'matricule' => 'EM1',
        'year' => 2024,
        'file' => 'annual_test/missing.pdf',
        'encryption_status' => AnnualDeclaration::STATUS_SUCCESSFUL,
    ]);

    app(AnnualDeclarationNotifier::class)->send($declaration);

    Mail::assertNothingSent();
    expect($declaration->fresh()->email_sent_status)->toBe(AnnualDeclaration::STATUS_FAILED);
});

test('orange delivery report updates the matching annual declaration', function () {
    $declaration = AnnualDeclaration::create([
        'send_annual_declaration_process_id' => $this->process->id,
        'company_id' => $this->company->id,
        'page_number' => 1,
        'page_status' => AnnualDeclaration::PAGE_MATCHED,
        'matricule' => 'EM1',
        'year' => 2024,
        'sms_txn_id' => 'txn-123',
        'sms_delivery_status' => 'pending',
        'sms_sent_at' => now(),
    ]);

    $route = collect(app('router')->getRoutes())->first(fn ($r) => str_contains($r->getActionName(), 'OrangeSmsWebhookController@deliveryReport'));
    expect($route)->not->toBeNull();

    $this->postJson('/' . ltrim($route->uri(), '/'), ['txnId' => 'txn-123', 'status' => 'DELIVRD'])->assertOk();

    expect($declaration->fresh()->sms_delivery_status)->toBe('delivered');
});

test('blocking process lookup prevents duplicate runs', function () {
    expect(SendAnnualDeclarationProcess::blockingProcessFor($this->company->id, $this->department->id, 2024))->not->toBeNull()
        ->and(SendAnnualDeclarationProcess::blockingProcessFor($this->company->id, $this->department->id, 2023))->toBeNull();
});
