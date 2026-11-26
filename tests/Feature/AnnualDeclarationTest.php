<?php

use App\Models\AnnualDeclaration;
use App\Models\SendAnnualDeclarationProcess;
use App\Services\AnnualDeclarationParser;
use Illuminate\Support\Facades\Schema;

test('annual declaration tables exist', function () {
    expect(Schema::hasTable('annual_declarations'))->toBeTrue()
        ->and(Schema::hasTable('send_annual_declaration_processes'))->toBeTrue()
        ->and(Schema::hasColumn('payslip_matching_proposals', 'document_type'))->toBeTrue();
});

test('parser rejects non declaration text', function () {
    expect(app(AnnualDeclarationParser::class)->isAnnualDeclarationText('Bulletin de paie'))->toBeFalse();
});

test('models are usable', function () {
    expect(AnnualDeclaration::query()->count())->toBe(0)
        ->and(SendAnnualDeclarationProcess::query()->count())->toBe(0);
});
