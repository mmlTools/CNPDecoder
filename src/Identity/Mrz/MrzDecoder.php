<?php

declare(strict_types=1);

namespace Identity\Mrz;

use Identity\Cnp\CnpDecoder;
use Identity\Cnp\CnpException;

final class MrzDecoder
{
    /**
     * Decodifică un șir MRZ (1/2/3 linii) în structură asociativă.
     * Normalizează inputul, determină formatul (TD1/TD2/TD3) și validează cifrele de control.
     *
     * @param string $mrz    Conținut MRZ (poate conține spații/NBSP/entități HTML).
     * @param bool   $strict true: aruncă excepții la erori; false: adaugă warnings și continuă.
     * @return array Rezultatul decodării (chei: format, document_type, issuing_country, etc.).
     * @throws MrzException Pentru formate invalide sau erori critice în modul strict.
     */
    public static function decode(string $mrz, bool $strict = true): array
    {
        $raw = html_entity_decode($mrz, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $raw = strtoupper(trim($raw));
        $raw = preg_replace('/\x{00A0}|\x{2007}|\x{202F}/u', ' ', $raw) ?? $raw;
        $raw = preg_replace('/[^A-Z0-9<\s]/u', '', $raw) ?? '';

        $lines = preg_split('/\s+/u', trim($raw));
        $lines = array_values(array_filter($lines, static fn($l) => $l !== ''));

        if (count($lines) === 1) {
            $lines = self::normalizeSingleLine($lines[0]);
        }

        $len1 = isset($lines[0]) ? strlen($lines[0]) : 0;

        return match (true) {
            count($lines) === 2 && $len1 === 44 => self::decodeTD3($lines, $strict),
            count($lines) === 2 && $len1 === 36 => self::decodeTD2($lines, $strict),
            count($lines) === 3 && $len1 === 30 => self::decodeTD1($lines, $strict),
            default => throw new MrzException('Format MRZ neacceptat (aștept TD3 2×44, TD2 2×36 sau TD1 3×30).'),
        };
    }

    /**
     * Normalizează o singură linie MRZ la format standard (TD3/TD2/TD1),
     * folosind padding cu '<' (≤5 caractere) și împărțire pe lungimi țintă.
     *
     * @param string $one Linia unică MRZ.
     * @return array Linii MRZ normalizate (2 sau 3 linii).
     * @throws MrzException Dacă nu se poate aproxima la 72/88/90 caractere.
     */
    private static function normalizeSingleLine(string $one): array
    {
        $L = strlen($one);
        $prefix2 = substr($one, 0, 2);

        $targets = [];
        if ($prefix2 === 'P<') {
            $targets = [88, 72, 90];
        } elseif ($prefix2 === 'ID' || $prefix2 === 'I<') {
            $targets = [72, 90, 88];
        } else {
            $targets = [88, 72, 90];
        }

        $padTo = function (string $s, int $target) {
            $len = strlen($s);
            if ($len === $target) return $s;
            if ($len < $target && ($target - $len) <= 5) return $s . str_repeat('<', $target - $len);
            return null;
        };

        foreach ($targets as $target) {
            $padded = $padTo($one, $target);
            if ($padded === null) continue;

            if ($target === 88) {
                return [substr($padded, 0, 44), substr($padded, 44, 44)];
            } elseif ($target === 72) {
                return [substr($padded, 0, 36), substr($padded, 36, 36)];
            } elseif ($target === 90) {
                return [substr($padded, 0, 30), substr($padded, 30, 30), substr($padded, 60, 30)];
            }
        }

        throw new MrzException('Lungime MRZ nesuportată pentru o singură linie: ' . $L . ' (nu se poate aproxima la 72/88/90).');
    }

    /**
     * Decodifică TD3 (Pașapoarte, 2×44).
     *
     * @param array $lines Cele 2 linii MRZ (44 de caractere fiecare).
     * @param bool  $strict Mod de validare (strict sau permissive cu warnings).
     * @return array Structură cu câmpurile extrase și validări.
     * @throws MrzException Pentru lungimi invalide sau erori critice în modul strict.
     */
    private static function decodeTD3(array $lines, bool $strict): array
    {
        [$l1, $l2] = $lines;
        if (strlen($l1) !== 44 || strlen($l2) !== 44) {
            throw new MrzException('Lungime TD3 invalidă (aștept 2×44).');
        }

        $warnings = [];

        $docType     = substr($l1, 0, 2);
        $issuing     = substr($l1, 2, 3);
        $namesField  = substr($l1, 5);
        [$primary, $secondary] = self::splitNames($namesField);

        $numberField = substr($l2, 0, 9);
        $number      = rtrim($numberField, '<');
        $numCheck    = self::toDigit($l2[9]);

        $nationality = substr($l2, 10, 3);
        $birthYYMMDD = substr($l2, 13, 6);
        $birthChk    = self::toDigit($l2[19]);

        $sex         = $l2[20];
        $expYYMMDD   = substr($l2, 21, 6);
        $expChk      = self::toDigit($l2[27]);

        $optData     = substr($l2, 28, 15);
        $allChk      = self::toDigit($l2[43]);

        self::assertCheckMaybe($numberField, $numCheck, 'document_number', $strict, $warnings);
        self::assertCheckMaybe($birthYYMMDD, $birthChk, 'birth_date',       $strict, $warnings);
        self::assertCheckMaybe($expYYMMDD,   $expChk,   'expiry_date',      $strict, $warnings);

        $composite = substr($l2, 0, 10)
            . substr($l2, 10, 3)
            . substr($l2, 13, 7)
            . $l2[20]
            . substr($l2, 21, 7)
            . substr($l2, 28, 15);
        self::assertCheckMaybe($composite, $allChk, 'composite', $strict, $warnings);

        $birthDate = self::toDateYyMmDd($birthYYMMDD, 'birth');
        $expDate   = self::toDateYyMmDd($expYYMMDD,   'expiry');
        $personalNumber = rtrim($optData, '<');

        $result = [
            'format'          => 'TD3',
            'document_type'   => $docType,
            'issuing_country' => $issuing,
            'nationality'     => $nationality,
            'document_number' => $number,
            'names'           => ['primary' => $primary, 'secondary' => $secondary],
            'birth'           => ['raw' => $birthYYMMDD, 'date' => $birthDate, 'sex' => self::toSex($sex)],
            'expiry'          => ['raw' => $expYYMMDD, 'date' => $expDate],
            'personal_number' => $personalNumber,
            'checks'          => ['number' => $numCheck, 'birth' => $birthChk, 'expiry' => $expChk, 'composite' => $allChk],
        ];
        if ($warnings) $result['warnings'] = $warnings;

        self::attachRomaniaCnp($result, $issuing, $nationality, $personalNumber, $sex, $birthDate, null);

        return $result;
    }

    /**
     * Decodifică TD2 (CI/Vize, 2×36). Acceptă compozit atât cu cât și fără naționalitate.
     *
     * @param array $lines Cele 2 linii MRZ (36 de caractere fiecare).
     * @param bool  $strict Mod de validare (strict sau permissive cu warnings).
     * @return array Structură cu câmpurile extrase și validări.
     * @throws MrzException Pentru lungimi invalide sau erori critice în modul strict.
     */
    private static function decodeTD2(array $lines, bool $strict): array
    {
        [$l1, $l2] = $lines;
        if (strlen($l1) !== 36 || strlen($l2) !== 36) {
            throw new MrzException('Lungime TD2 invalidă (aștept 2×36).');
        }

        $warnings = [];

        $docType     = substr($l1, 0, 2);
        $issuing     = substr($l1, 2, 3);
        $namesField  = substr($l1, 5, 31);
        [$primary, $secondary] = self::splitNames($namesField);

        $numberField = substr($l2, 0, 9);
        $number      = rtrim($numberField, '<');
        $numCheck    = self::toDigit($l2[9]);

        $nationality = substr($l2, 10, 3);
        $birthYYMMDD = substr($l2, 13, 6);
        $birthChk    = self::toDigit($l2[19]);

        $sex         = $l2[20];
        $expYYMMDD   = substr($l2, 21, 6);
        $expChk      = self::toDigit($l2[27]);

        $optData     = substr($l2, 28, 7);
        $allChk      = self::toDigit($l2[35]);

        self::assertCheckMaybe($numberField, $numCheck, 'document_number', $strict, $warnings);
        self::assertCheckMaybe($birthYYMMDD, $birthChk, 'birth_date',      $strict, $warnings);
        self::assertCheckMaybe($expYYMMDD,   $expChk,   'expiry_date',     $strict, $warnings);

        $compA = substr($l2, 0, 10) . substr($l2, 10, 3) . substr($l2, 13, 7) . $l2[20] . substr($l2, 21, 7) . substr($l2, 28, 7);
        $compB = substr($l2, 0, 10)                    . substr($l2, 13, 7) . $l2[20] . substr($l2, 21, 7) . substr($l2, 28, 7);
        $calcA = self::computeCheckDigit($compA);
        $calcB = self::computeCheckDigit($compB);

        if ($calcA !== $allChk && $calcB !== $allChk) {
            $isRomania = ($issuing === 'ROU' || $nationality === 'ROU');
            if ($isRomania) {
                // unele CI RO folosesc o variantă non-standard; nu mai raporta warning
                // dacă vrei, poți atașa o notă informativă:
                // $warnings[] = "Compozit RO non-standard: aștept {$allChk}, calc A={$calcA}, B={$calcB} (ignorat)";
            } else {
                $msg = "Cifră de control invalidă pentru composite: aștept {$allChk}, am calculat {$calcA} (incl. NAT) / {$calcB} (fără NAT)";
                if ($strict) throw new MrzException($msg);
                $warnings[] = $msg;
            }
        }

        $birthDate = self::toDateYyMmDd($birthYYMMDD, 'birth');
        $expDate   = self::toDateYyMmDd($expYYMMDD,   'expiry');
        $personalNumber = rtrim($optData, '<');

        $result = [
            'format'          => 'TD2',
            'document_type'   => $docType,
            'issuing_country' => $issuing,
            'nationality'     => $nationality,
            'document_number' => $number,
            'names'           => ['primary' => $primary, 'secondary' => $secondary],
            'birth'           => ['raw' => $birthYYMMDD, 'date' => $birthDate, 'sex' => self::toSex($sex)],
            'expiry'          => ['raw' => $expYYMMDD, 'date' => $expDate],
            'personal_number' => $personalNumber,
            'checks'          => [
                'number'    => $numCheck,
                'birth'     => $birthChk,
                'expiry'    => $expChk,
                'composite' => $allChk,
            ],
        ];
        if ($warnings) $result['warnings'] = $warnings;

        self::attachRomaniaCnp($result, $issuing, $nationality, $personalNumber, $sex, $birthDate, $optData);

        return $result;
    }

    /**
     * Decodifică TD1 (CI pe 3×30).
     *
     * @param array $lines Cele 3 linii MRZ (30 de caractere fiecare).
     * @param bool  $strict Mod de validare (strict sau permissive cu warnings).
     * @return array Structură cu câmpurile extrase și validări.
     * @throws MrzException Pentru lungimi invalide sau erori critice în modul strict.
     */
    private static function decodeTD1(array $lines, bool $strict): array
    {
        [$l1, $l2, $l3] = $lines;
        foreach ($lines as $ln) {
            if (strlen($ln) !== 30) {
                throw new MrzException('Lungime TD1 invalidă (aștept 3×30).');
            }
        }

        $warnings = [];

        $docType     = substr($l1, 0, 2);
        $issuing     = substr($l1, 2, 3);
        $numberField = substr($l1, 5, 9);
        $number      = rtrim($numberField, '<');
        $numCheck    = self::toDigit($l1[14]);
        $opt1        = substr($l1, 15, 15);

        $birthYYMMDD = substr($l2, 0, 6);
        $birthChk    = self::toDigit($l2[6]);
        $sex         = $l2[7];
        $expYYMMDD   = substr($l2, 8, 6);
        $expChk      = self::toDigit($l2[14]);
        $nationality = substr($l2, 15, 3);
        $opt2        = substr($l2, 18, 12);

        $namesField  = $l3;
        [$primary, $secondary] = self::splitNames($namesField);

        self::assertCheckMaybe($numberField, $numCheck, 'document_number', $strict, $warnings);
        self::assertCheckMaybe($birthYYMMDD, $birthChk, 'birth_date',      $strict, $warnings);
        self::assertCheckMaybe($expYYMMDD,   $expChk,   'expiry_date',     $strict, $warnings);

        $finalChk = null;
        if (isset($l2[29]) && ctype_digit($l2[29])) {
            $finalChk = self::toDigit($l2[29]);
            // Compozit TD1 opțional (depinde de emitent) – dezactivat implicit:
            // $composite = substr($l1, 5, 25) . substr($l2, 0, 7) . substr($l2, 8, 7) . substr($l2, 15, 14);
            // self::assertCheckMaybe($composite, $finalChk, 'composite', $strict, $warnings);
        }

        $birthDate = self::toDateYyMmDd($birthYYMMDD, 'birth');
        $expDate   = self::toDateYyMmDd($expYYMMDD,   'expiry');
        $optData   = substr($l2, 15, 15);
        $personalNumber = rtrim($opt1 . $opt2, '<');

        $result = [
            'format'          => 'TD1',
            'document_type'   => $docType,
            'issuing_country' => $issuing,
            'nationality'     => $nationality,
            'document_number' => $number,
            'names'           => ['primary' => $primary, 'secondary' => $secondary],
            'birth'           => ['raw' => $birthYYMMDD, 'date' => $birthDate, 'sex' => self::toSex($sex)],
            'expiry'          => ['raw' => $expYYMMDD, 'date' => $expDate],
            'personal_number' => $personalNumber,
            'checks'          => [
                'number'    => $numCheck,
                'birth'     => $birthChk,
                'expiry'    => $expChk,
                'composite' => $finalChk,
            ],
        ];
        if ($warnings) $result['warnings'] = $warnings;

        self::attachRomaniaCnp($result, $issuing, $nationality, $personalNumber, $sex, $birthDate, $optData);

        return $result;
    }

    /**
     * Sparge câmpul de nume MRZ (PRIMARY<<SECONDARY<ALTE<NUME) în [primary, secondary].
     *
     * @param string $field Câmpul brut de nume din MRZ.
     * @return array [primary, secondary] curățate de separatori.
     */
    private static function splitNames(string $field): array
    {
        $parts = explode('<<', $field, 2);
        $primary = str_replace('<', ' ', rtrim($parts[0], '<'));
        $secondary = isset($parts[1]) ? str_replace('<', ' ', rtrim($parts[1], '<')) : '';
        $primary = trim(preg_replace('/\s+/', ' ', $primary));
        $secondary = trim(preg_replace('/\s+/', ' ', $secondary));
        return [$primary, $secondary];
    }

    /**
     * Normalizează sexul (M/F) sau returnează "X" pentru necunoscut.
     *
     * @param string $c Caracterul sexului din MRZ.
     * @return string 'M', 'F' sau 'X'.
     */
    private static function toSex(string $c): string
    {
        return match ($c) {
            'M' => 'M',
            'F' => 'F',
            default => 'X',
        };
    }

    /**
     * Convertește un caracter cifră MRZ în întreg (0–9).
     *
     * @param string $c Caracterul de verificare.
     * @return int Valoarea numerică 0–9.
     * @throws MrzException Dacă nu este o cifră.
     */
    private static function toDigit(string $c): int
    {
        if ($c >= '0' && $c <= '9') return ord($c) - 48;
        throw new MrzException("Caracter invalid pentru cifră de control: '{$c}'");
    }

    /**
     * Verificare ICAO 7–3–1 (cu opțiune strict/warnings).
     *
     * @param string $data     Câmp MRZ brut (inclusiv '<').
     * @param int    $expected Cifra de control așteptată.
     * @param string $label    Etichetă câmp (în mesaje).
     * @param bool   $strict   Comportament strict sau permisiv.
     * @param array  $warnings Colector de avertizări.
     * @return void
     * @throws MrzException În modul strict, la nepotrivire.
     */
    private static function assertCheckMaybe(string $data, int $expected, string $label, bool $strict, array &$warnings): void
    {
        $calc = self::computeCheckDigit($data);
        if ($calc !== $expected) {
            $msg = "Cifră de control invalidă pentru {$label}: aștept {$expected}, am calculat {$calc}";
            if ($strict) throw new MrzException($msg);
            $warnings[] = $msg;
        }
    }

    /**
     * Convertește un caracter MRZ în valoarea numerică folosită la 7–3–1 (A=10..Z=35, '<'=0).
     *
     * @param string $ch Caracter MRZ.
     * @return int Valoare numerică MRZ.
     * @throws MrzException Pentru caractere invalide.
     */
    private static function charValue(string $ch): int
    {
        if ($ch >= '0' && $ch <= '9') return ord($ch) - 48;
        if ($ch >= 'A' && $ch <= 'Z') return ord($ch) - 55;
        if ($ch === '<') return 0;
        throw new MrzException("Caracter MRZ invalid: '{$ch}'");
    }

    /**
     * Calculează cifra de control MRZ (ICAO Doc 9303) cu ponderi [7,3,1].
     *
     * @param string $data Câmpul MRZ brut (inclusiv '<').
     * @return int Cifra de control (0–9).
     */
    private static function computeCheckDigit(string $data): int
    {
        $weights = [7, 3, 1];
        $sum = 0;
        $n = strlen($data);
        for ($i = 0; $i < $n; $i++) {
            $sum += self::charValue($data[$i]) * $weights[$i % 3];
        }
        return $sum % 10;
    }

    /**
     * Convertește data YYMMDD în format YYYY-MM-DD.
     * Pentru 'birth' decide secolul relativ la anul curent; pentru 'expiry' forțează 2000–2099.
     *
     * @param string $six     YYMMDD.
     * @param string $context 'birth' sau 'expiry'.
     * @return string|null Data ISO sau null dacă e invalidă.
     */
    private static function toDateYyMmDd(string $six, string $context = 'birth'): ?string
    {
        if (!preg_match('/^\d{6}$/', $six)) return null;
        $yy = (int)substr($six, 0, 2);
        $mm = (int)substr($six, 2, 2);
        $dd = (int)substr($six, 4, 2);

        if ($context === 'expiry') {
            $year = 2000 + $yy;
        } else {
            $curYY = (int)date('y');
            $year = ($yy > $curYY) ? (1900 + $yy) : (2000 + $yy);
        }

        if (!checkdate($mm, $dd, $year)) return null;
        return sprintf('%04d-%02d-%02d', $year, $mm, $dd);
    }

    /**
     * Calculează cifra de control CNP pentru primele 12 cifre (S,AA,LL,ZZ,JJ,NNN).
     *
     * @param string $first12 Primele 12 cifre ale CNP.
     * @return int Cifra de control CNP.
     * @throws MrzException Dacă intrarea nu are 12 cifre.
     */
    private static function cnpControlFor12(string $first12): int
    {
        if (!preg_match('/^\d{12}$/', $first12)) {
            throw new MrzException('Intrare invalidă pentru control CNP (aștept 12 cifre).');
        }
        $weights = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int)$first12[$i]) * $weights[$i];
        }
        $mod = $sum % 11;
        return ($mod === 10) ? 1 : $mod;
    }

    /**
     * Recalculează cifra compozită pentru linia 2 TD2 (varianta fără naționalitate).
     * Utilitar de debug.
     *
     * @param string $l2 Linia 2 TD2 (36 caractere).
     * @return int Cifra de control recalculată.
     * @throws MrzException Pentru lungime invalidă.
     */
    private static function recomputeTd2CompositeDigit(string $l2): int
    {
        if (strlen($l2) !== 36) throw new MrzException('Linia 2 TD2 trebuie să aibă 36 de caractere.');
        $compB = substr($l2, 0, 10) . substr($l2, 13, 7) . $l2[20] . substr($l2, 21, 7) . substr($l2, 28, 7);
        return self::computeCheckDigit($compB);
    }

    /**
     * Atașează în rezultat CNP pentru documente RO: folosește personal_number (13 cifre) sau
     * reconstruiește din ultimii 6 digiti ai opționalului (JJ+NNN+C) + S derivat din sex/secol.
     *
     * @param array       $result          Structura de rezultat (modificată in-place).
     * @param string      $issuing         Țara emitentă.
     * @param string      $nationality     Naționalitatea.
     * @param string      $personalNumber  Câmpul opțional curățat de '<'.
     * @param string      $sexChar         Sexul din MRZ (M/F/X).
     * @param string|null $birthDate       Data nașterii ISO.
     * @param string|null $optionalRaw     Câmpul opțional brut (cu '<').
     * @return void
     */
    private static function attachRomaniaCnp(
        array &$result,
        string $issuing,
        string $nationality,
        string $personalNumber,
        string $sexChar,
        ?string $birthDate,
        ?string $optionalRaw
    ): void {
        $isRO = ($issuing === 'ROU' || $nationality === 'ROU');
        if (!$isRO) return;

        if (preg_match('/^\d{13}$/', $personalNumber)) {
            try {
                $cnp = new CnpDecoder($personalNumber);
                $result['romania'] = ['cnp' => $cnp->toArray()];
                return;
            } catch (CnpException $e) {
                $result['romania'] = ['cnp_error' => $e->message(), 'code' => $e->getCode()];
                return;
            }
        }

        if ($birthDate && $optionalRaw) {
            $digits = preg_replace('/\D+/', '', $optionalRaw) ?? '';
            if (strlen($digits) >= 6) {
                $tail6 = substr($digits, -6);
                $JJ    = substr($tail6, 0, 2);
                $NNN   = substr($tail6, 2, 3);

                $year = (int)substr($birthDate, 0, 4);
                $yy   = substr($birthDate, 2, 2);
                $mm   = substr($birthDate, 5, 2);
                $dd   = substr($birthDate, 8, 2);

                $isFemale = (strtoupper($sexChar) === 'F');
                $S = ($year >= 2000) ? ($isFemale ? '6' : '5') : ($isFemale ? '2' : '1');

                $first12 = $S . $yy . $mm . $dd . $JJ . $NNN;

                try {
                    $C = (string) self::cnpControlFor12($first12);
                    $candidate = $first12 . $C;

                    $cnp = new CnpDecoder($candidate);
                    $result['romania']['cnp_reconstruit'] = $cnp->toArray();
                } catch (\Throwable $e) {
                    $result['romania']['cnp_reconstruit_error'] = $e->getMessage();
                    $result['romania']['cnp_reconstruit_raw_12'] = $first12;
                }
            }
        }
    }
}

final class MrzException extends \Exception {}
