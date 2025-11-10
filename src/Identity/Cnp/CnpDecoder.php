<?php
declare(strict_types=1);

namespace Identity\Cnp;

use Identity\Cnp\CnpException;

/**
 * Author: Marco Maxim
 * Website: https://mmltools.com
 * Class CNPDecoder
 * Structură: S AA LL ZZ JJ NNN C
 * S - Componenta S reprezintă sexul și secolul în care s-a născut persoana
 *      1 pentru persoanele de sex masculin născute între anii 1900 - 1999
 *      2 pentru persoanele de sex feminin născute între anii 1900 - 1999
 *      3 pentru persoanele de sex masculin născute între anii 1800 - 1899
 *      4 pentru persoanele de sex feminin născute între anii 1800 - 1899
 *      5 pentru persoanele de sex masculin născute între anii 2000 - 2099
 *      6 pentru persoanele de sex feminin născute între anii 2000 - 2099
 *      7 pentru persoanele rezidente, de sex masculin
 *      8 pentru persoanele rezidente, de sex feminin
 *
 * AA - Componenta AA este formată din ultimele 2 cifre ale anului nașterii.
 * LL - Componenta LL este formată din luna nașterii, cu valori între 01 și 12.
 * ZZ - Componenta ZZ este formată din ziua nașterii, cu valori între 01 și 28, 29, 30 sau 31, după caz.
 * JJ - Componenta JJ reprezintă județul sau sectorul în care s-a născut persoana, ori în care avea domiciliul sau reședința la momentul acordării C.N.P. conform nomenclatorului SIRUTA.
 * NNN - Componenta NNN reprezintă un număr secvențial ( cuprins între 001 și 999 ), repartizat pe puncte de atribuire, prin care se diferențiază persoanele de același sex, născute în același loc și cu aceeași dată de naștere.
 * C - Componenta C este formată dintr-o cifră de control⁠(en), care permite depistarea eventualelor erori de înlocuire sau inversare a cifrelor din componența C.N.P
 */

final class CnpDecoder
{
    /** Componente CNP */
    private int $S;
    private int $YY;
    private int $MM;
    private int $DD;
    private int $JJ;
    private string $NNN;
    private int $C;

    /** Nomenclator județe (JJ → denumire) */
    private const COUNTIES = [
        1 => 'Alba', 2 => 'Arad', 3 => 'Argeș', 4 => 'Bacău', 5 => 'Bihor', 6 => 'Bistrița-Năsăud',
        7 => 'Botoșani', 8 => 'Brașov', 9 => 'Brăila', 10 => 'Buzău', 11 => 'Caraș-Severin', 12 => 'Cluj',
        13 => 'Constanța', 14 => 'Covasna', 15 => 'Dâmbovița', 16 => 'Dolj', 17 => 'Galați', 18 => 'Gorj',
        19 => 'Harghita', 20 => 'Hunedoara', 21 => 'Ialomița', 22 => 'Iași', 23 => 'Ilfov', 24 => 'Maramureș',
        25 => 'Mehedinți', 26 => 'Mureș', 27 => 'Neamț', 28 => 'Olt', 29 => 'Prahova', 30 => 'Satu Mare',
        31 => 'Sălaj', 32 => 'Sibiu', 33 => 'Suceava', 34 => 'Teleorman', 35 => 'Timiș', 36 => 'Tulcea',
        37 => 'Vaslui', 38 => 'Vâlcea', 39 => 'Vrancea', 40 => 'București',
        41 => 'București - Sector 1', 42 => 'București - Sector 2', 43 => 'București - Sector 3',
        44 => 'București - Sector 4', 45 => 'București - Sector 5', 46 => 'București - Sector 6',
        51 => 'Călărași', 52 => 'Giurgiu',
        47 => 'București - Sector 7 (desființat)', 48 => 'București - Sector 8 (desființat)',
    ];

    /**
     * Constructor: primește CNP-ul brut, îl curăță și validează.
     * @param string $cnp CNP din 13 cifre.
     * @throws CnpException Dacă CNP-ul nu trece validările.
     */
    public function __construct(private string $cnp)
    {
        $this->cnp = \trim($this->cnp);
        $this->validate();
    }

    /**
     * Validează formatul și componentele CNP (lungime, cifre, dată, județ, checksum).
     * @return void
     * @throws CnpException Coduri: 100 (lungime), 101 (non-cifre), 102 (S), 103 (lună),
     *                      104 (zi/dată), 105 (județ), 106 (cifră control).
     */
    private function validate(): void
    {
        if (\strlen($this->cnp) !== 13) {
            throw new CnpException('', 100);
        }
        if (!\ctype_digit($this->cnp)) {
            throw new CnpException('', 101);
        }

        $this->S  = (int)$this->cnp[0];
        $this->YY = (int)($this->cnp[1].$this->cnp[2]);
        $this->MM = (int)($this->cnp[3].$this->cnp[4]);
        $this->DD = (int)($this->cnp[5].$this->cnp[6]);
        $this->JJ = (int)($this->cnp[7].$this->cnp[8]);
        $this->NNN = $this->cnp[9].$this->cnp[10].$this->cnp[11];
        $this->C   = (int)$this->cnp[12];

        if ($this->S < 1 || $this->S > 8) {
            throw new CnpException('', 102);
        }
        if ($this->MM < 1 || $this->MM > 12) {
            throw new CnpException('', 103);
        }

        $year = $this->birthYear();
        if (!\checkdate($this->MM, $this->DD, $year)) {
            throw new CnpException('', 104);
        }
        if (!\array_key_exists($this->JJ, self::COUNTIES)) {
            throw new CnpException('', 105);
        }
        if ($this->computeChecksum() !== $this->C) {
            throw new CnpException('', 106);
        }
    }

    /**
     * Calculează anul complet al nașterii pe baza componentei S (secol) și a AA.
     * @return int Anul nașterii (YYYY).
     */
    private function birthYear(): int
    {
        return match ($this->S) {
            1,2 => 1900 + $this->YY,
            3,4 => 1800 + $this->YY,
            5,6 => 2000 + $this->YY,
            7,8 => (2000 + $this->YY) > (int)\date('Y') ? 1900 + $this->YY : 1900 + $this->YY,
            default => 0,
        };
    }

    /**
     * Calculează cifra de control CNP pentru primele 12 cifre (cheia 279146358279).
     * @return int Cifra de control (0–9; 10 → 1).
     */
    private function computeChecksum(): int
    {
        $key = '279146358279';
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int)$this->cnp[$i]) * ((int)$key[$i]);
        }
        $mod = $sum % 11;
        return $mod === 10 ? 1 : $mod;
    }

    /**
     * Anul nașterii (YYYY).
     * @return int
     */
    public function getBirthYear(): int
    {
        return $this->birthYear();
    }

    /**
     * Data nașterii în format ISO (YYYY-MM-DD).
     * @return string
     */
    public function getBirthDate(): string
    {
        return \sprintf('%04d-%02d-%02d', $this->birthYear(), $this->MM, $this->DD);
    }

    /**
     * Vârsta în ani împliniți raportată la ziua curentă.
     * @return int
     */
    public function getAge(): int
    {
        $today = new \DateTimeImmutable('today');
        $born  = new \DateTimeImmutable($this->getBirthDate());
        return $born->diff($today)->y;
    }

    /**
     * Genul: 1 = masculin (S ∈ {1,3,5,7}), 2 = feminin (S ∈ {2,4,6,8}).
     * @return int
     */
    public function getGender(): int
    {
        return \in_array($this->S, [1,3,5,7], true) ? 1 : 2;
    }

    /**
     * Cod județ (JJ).
     * @return int
     */
    public function getCountyCode(): int
    {
        return $this->JJ;
    }

    /**
     * Denumire județ conform nomenclatorului.
     * @return string
     */
    public function getCountyName(): string
    {
        return self::COUNTIES[$this->JJ];
    }

    /**
     * Secvența NNN (string de 3 cifre).
     * @return string
     */
    public function getSequence(): string
    {
        return $this->NNN;
    }

    /**
     * Cifra de control C.
     * @return int
     */
    public function getControlDigit(): int
    {
        return $this->C;
    }

    /**
     * Zile rămase până la următoarea aniversare (0 dacă este astăzi).
     * @return int
     */
    public function daysUntilBirthday(): int
    {
        $now  = new \DateTimeImmutable('today');
        $year = (int)$now->format('Y');
        $next = \DateTimeImmutable::createFromFormat(
            'Y-m-d',
            \sprintf('%04d-%02d-%02d', $year, $this->MM, $this->DD)
        );
        if ($next < $now) {
            $next = $next->modify('+1 year');
        }
        return (int)$now->diff($next)->format('%a');
    }

    /**
     * Exportă componentele CNP într-un array asociativ.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'cnp'             => $this->cnp,
            'birth_date'      => $this->getBirthDate(),
            'birth_year'      => $this->getBirthYear(),
            'age'             => $this->getAge(),
            'gender'          => $this->getGender(),     // 1 M, 2 F
            'county_code'     => $this->getCountyCode(),
            'county_name'     => $this->getCountyName(),
            'sequence'        => $this->getSequence(),
            'control_digit'   => $this->getControlDigit(),
            'days_to_birthday'=> $this->daysUntilBirthday(),
        ];
    }
}
