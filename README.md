# 🇷🇴 PHP CNP & MRZ Decoder

O bibliotecă PHP modernă care permite:
- extragerea și validarea componentelor unui **CNP românesc** (sex, vârstă, județ, ziua de naștere etc.);
- decodarea completă a unui **MRZ** (Machine Readable Zone) din documentele de identitate românești și internaționale (TD1, TD2, TD3), inclusiv reconstruirea CNP-ului pentru România.

---

## 📦 Instalare (Composer)

```bash
composer require mmltech/cnp-decoder
```

---

## 🧩 1. Utilizare — decodare CNP directă

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Identity\Cnp\CnpDecoder;

// Atentie: A se utiliza un CNP valid altfel se va returna un warning la cifra de control
$cnp = new CnpDecoder('-------------');

print_r($cnp->toArray());
```

### ✅ Rezultat exemplu

```php
Array
(
    [cnp] => -------------
    [birth_date] => 0000-00-00
    [birth_year] => 0000
    [age] => 00
    [gender] => 0
    [county_code] => 00
    [county_name] => -----
    [sequence] => 0000
    [control_digit] => 0
    [days_to_birthday] => 0
)
```

### 🔍 Metode disponibile (`CnpDecoder`)

| Metodă | Descriere |
|--------|------------|
| `__construct(string $cnp)` | Creează o nouă instanță și validează automat CNP-ul. |
| `getBirthYear()` | Returnează anul complet (ex: `1991`). |
| `getBirthDate()` | Returnează data nașterii în format ISO (`YYYY-MM-DD`). |
| `getAge()` | Returnează vârsta actuală în ani. |
| `getGender()` | Returnează `1` pentru masculin, `2` pentru feminin. |
| `getCountyCode()` | Cod numeric al județului (JJ). |
| `getCountyName()` | Denumirea județului conform nomenclatorului SIRUTA. |
| `getSequence()` | Numărul secvențial (NNN). |
| `getControlDigit()` | Cifra de control calculată cu cheia `279146358279`. |
| `daysUntilBirthday()` | Zile rămase până la următoarea aniversare. |
| `toArray()` | Export complet al datelor într-un array asociativ. |

---

## 🧠 2. Utilizare — decodare MRZ (TD1 / TD2 / TD3)

Biblioteca include și `MrzDecoder`, compatibil cu standardul ICAO Doc 9303 și cu MRZ-urile de pe **CI românești**.

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Identity\Mrz\MrzDecoder;
use Identity\Cnp\CnpDecoder;

$mrz = <<<MRZ
IDROUXXXXX<<XXXXX<<<<<<<<<<<<<<<<<<<
SX000000<1ROU0000007M260000410000006
MRZ;

// Decodare automată (detectează TD1 / TD2 / TD3)
$result = MrzDecoder::decode($mrz, false);

// Afișare date MRZ
echo "<pre>", print_r($result, true), "</pre>";

// Dacă există CNP reconstruit, îl poți valida suplimentar
if (isset($result['romania']['cnp_reconstruit']['cnp'])) {
    $cnp = new CnpDecoder($result['romania']['cnp_reconstruit']['cnp']);
    echo "<pre>", print_r($cnp->toArray(), true), "</pre>";
}
```

---

### 🔍 Metode disponibile (`MrzDecoder`)

| Metodă | Descriere |
|--------|------------|
| `decode(string $mrz, bool $strict = true): array` | Decodifică automat orice MRZ TD1, TD2, TD3. |
| `normalizeSingleLine(string $raw): array` | Normalizează MRZ-ul dacă este o singură linie (OCR). |
| `toSex(string $char): string` | Convertește codul de sex (M/F/‹). |
| `toDateYyMmDd(string $raw, string $context): string` | Formatează datele YYMMDD în format ISO (cu secolul dedus). |
| `computeCheckDigit(string $input): int` | Calculează cifra de control conform standardului ICAO (MRZ). |

Pentru documentele românești (TD2 CI RO), metoda gestionează **automat**:
- diferențele de calcul ale câmpului *composite check* (cu sau fără naționalitate);
- reconstruirea CNP-ului complet, inclusiv cifra de control.

---

## 🧬 3. Rezultat tipic pentru MRZ românesc (TD2)

```php
Array
(
    [format] => TD2
    [document_type] => ID
    [issuing_country] => ROU
    [nationality] => ROU
    [document_number] => SX477668
    [names] => Array
        (
            [primary] => XXXXX
            [secondary] => XXXXX
        )

    [birth] => Array
        (
            [raw] => 000000
            [date] => 0000-00-00
            [sex] => M
        )

    [expiry] => Array
        (
            [raw] => 000000
            [date] => 0000-00-00
        )

    [personal_number] => 0000000
    [checks] => Array
        (
            [number] => 0
            [birth] => 0
            [expiry] => 0
            [composite] => 0
        )

    [romania] => Array
        (
            [cnp_reconstruit] => Array
                (
                    [cnp] => 0000000000000
                    [birth_date] => 0000-00-00
                    [birth_year] => 0000
                    [age] => 00
                    [gender] => 0
                    [county_code] => 00
                    [county_name] => ----
                    [sequence] => 000
                    [control_digit] => 0
                    [days_to_birthday] => 0
                )

        )
)
```

---

## ⚙️ Integrare rapidă

```php
use Identity\Mrz\MrzDecoder;
use Identity\Cnp\CnpDecoder;

// Decodare MRZ dintr-un upload OCR
$decoded = MrzDecoder::decode($_POST['mrz'], false);

// Dacă e România, validează CNP-ul reconstruit
if (isset($decoded['romania']['cnp_reconstruit']['cnp'])) {
    $cnp = new CnpDecoder($decoded['romania']['cnp_reconstruit']['cnp']);
    $info = $cnp->toArray();
    echo json_encode($info, JSON_PRETTY_PRINT);
}
```

---

## 🧾 Licență

Distribuit sub licența **GPL-3.0-or-later**

**Autor:** [Marco Maxim](https://obscountdown.com)  
**GitHub:** [github.com/mmltech](https://github.com/mmltech)
