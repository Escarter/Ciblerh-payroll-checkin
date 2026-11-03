<?php

use App\Services\AnnualDeclarationParser;

function declarationText(string $matricule = 'EM1001', string $amount = '1 200 000', int $year = 2024): string
{
    $amountLine = $amount !== '' ? "au cours de la période : {$amount}" : 'au cours de la période :';

    return <<<TXT
EMPLOI SERVICE - DECLARATION INDIVIDUELLE DES REVENUS SALARIAUX - {$year}

Nom :    DOE                    {$matricule}            NUI :

Prénom : Jane                                                    N° CNPS : 0000

     1- Montants payés en espèces ou crédités en compte {$amountLine}

     2- Impôts & taxes retenues sur le salaire :

      Irpp                                           100 000
     Cac/irpp                                        10 000
     Tdl                                             2 000
     Cfc Sal                                         3 000
     Rav                                             4 000
TXT;
}

test('parses a populated declaration page', function () {
    $r = app(AnnualDeclarationParser::class)->parseText(declarationText());

    expect($r['is_declaration'])->toBeTrue()
        ->and($r['year'])->toBe(2024)
        ->and($r['matricule'])->toBe('EM1001')
        ->and($r['gross_amount'])->toBe(1200000.0)
        ->and($r['tax_total'])->toBe(119000.0)
        ->and($r['is_empty'])->toBeFalse();
});

test('flags a page without amounts as empty', function () {
    $r = app(AnnualDeclarationParser::class)->parseText(declarationText('EM1002', ''));

    expect($r['matricule'])->toBe('EM1002')->and($r['is_empty'])->toBeTrue();
});

test('non declaration text is not recognised', function () {
    $r = app(AnnualDeclarationParser::class)->parseText('BULLETIN DE PAIE janvier 2024');

    expect($r['is_declaration'])->toBeFalse()->and($r['matricule'])->toBeNull();
});
