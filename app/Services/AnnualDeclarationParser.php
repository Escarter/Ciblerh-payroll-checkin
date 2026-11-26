<?php

namespace App\Services;

use App\Models\User;
use Escarter\PopplerPhp\PdfToText;

class AnnualDeclarationParser
{
    public const TITLE_MARKER = 'DECLARATION INDIVIDUELLE DES REVENUS SALARIAUX';

    /**
     * Parse the text of a single declaration page.
     *
     * @return array{is_declaration:bool,year:?int,matricule:?string,name:?string,cnps_number:?string,gross_amount:?float,tax_total:?float,is_empty:bool}
     */
    public function parseText(string $text): array
    {
        $text = User::sanitizePdfTextToUtf8($text);
        $text = str_replace("\u{00A0}", ' ', $text);

        $result = [
            'is_declaration' => $this->isAnnualDeclarationText($text),
            'year' => null,
            'matricule' => null,
            'name' => null,
            'cnps_number' => null,
            'gross_amount' => null,
            'tax_total' => null,
            'is_empty' => true,
        ];

        if (preg_match('/REVENUS\s+SALARIAUX\s*-\s*(\d{4})/iu', $text, $m)) {
            $result['year'] = (int) $m[1];
        }

        // "Nom : <name>   EM4499   NUI :" - the matricule sits on the Nom line, right of the name.
        if (preg_match('/^\s*Nom\s*:\s*(.*?)\s+([A-Z]{1,4}\d{2,})\s+NUI\b/mu', $text, $m)) {
            $result['name'] = trim(preg_replace('/\s+/', ' ', $m[1]));
            $result['matricule'] = User::normalizeMatricule($m[2]);
        }

        if (preg_match('/^\s*Pr[ée]nom\s*:\s*(.*?)\s{2,}/mu', $text . "  ", $m)) {
            $firstName = trim($m[1]);
            if ($firstName !== '' && $result['name'] !== null) {
                $result['name'] = trim($result['name'] . ' ' . $firstName);
            }
        }

        if (preg_match('/N°\s*CNPS\s*:\s*([0-9 ]*)/u', $text, $m)) {
            $cnps = trim($m[1]);
            $result['cnps_number'] = $cnps !== '' ? $cnps : null;
        }

        $result['gross_amount'] = $this->extractAmount('/p[ée]riode[ \t]*:[ \t]*([\d ]+)/iu', $text);
        $result['tax_total'] = $this->sumTaxes($text);
        $result['is_empty'] = $result['gross_amount'] === null || $result['gross_amount'] <= 0;

        return $result;
    }

    public function parseFile(string $absolutePath): array
    {
        $text = PdfToText::getText($absolutePath, config('ciblerh.pdftotext_path'), ['layout']);

        return $this->parseText((string) $text);
    }

    public function isAnnualDeclarationText(string $text): bool
    {
        return mb_stripos($text, self::TITLE_MARKER) !== false;
    }

    private function extractAmount(string $pattern, string $text): ?float
    {
        if (!preg_match($pattern, $text, $m)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $m[1]);

        return $digits === '' ? null : (float) $digits;
    }

    private function sumTaxes(string $text): ?float
    {
        $total = 0.0;
        $found = false;

        foreach (['Irpp', 'Cac\/irpp', 'Tdl', 'Cfc Sal', 'Rav'] as $label) {
            if (preg_match('/^\s*' . $label . '[ \t]+([\d ]+)[ \t]*$/imu', $text, $m)) {
                $digits = preg_replace('/\D/', '', $m[1]);
                if ($digits !== '') {
                    $total += (float) $digits;
                    $found = true;
                }
            }
        }

        return $found ? $total : null;
    }
}
