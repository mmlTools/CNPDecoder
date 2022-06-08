# PHP CNP Decoder
 
 O clasă PHP simplă prin care se pot extrage componentele aferente CNP-ului introdus, componente precum vârsta persoanei, zile rămase până la ziua de naștere, județul în care s-a născut persoana în cauză, genul, anul nașterii etc.
 
 ### Author: Marco Maxim
 ### Website: https://mmltools.com
 ### Class CNPDecoder
 ### Structură: S AA LL ZZ JJ NNN C
 * S - Componenta S reprezintă sexul și secolul în care s-a născut persoana
 *      1 pentru persoanele de sex masculin născute între anii 1900 - 1999
 *      2 pentru persoanele de sex feminin născute între anii 1900 - 1999
 *      3 pentru persoanele de sex masculin născute între anii 1800 - 1899
 *      4 pentru persoanele de sex feminin născute între anii 1800 - 1899
 *      5 pentru persoanele de sex masculin născute între anii 2000 - 2099
 *      6 pentru persoanele de sex feminin născute între anii 2000 - 2099
 *      7 pentru persoanele rezidente, de sex masculin
 *      8 pentru persoanele rezidente, de sex feminin
 * AA - Componenta AA este formată din ultimele 2 cifre ale anului nașterii.
 * LL - Componenta LL este formată din luna nașterii, cu valori între 01 și 12.
 * ZZ - Componenta ZZ este formată din ziua nașterii, cu valori între 01 și 28, 29, 30 sau 31, după caz.
 * JJ - Componenta JJ reprezintă județul sau sectorul în care s-a născut persoana, ori în care avea domiciliul sau reședința la momentul acordării C.N.P. conform nomenclatorului SIRUTA.
 * NNN - Componenta NNN reprezintă un număr secvențial ( cuprins între 001 și 999 ), repartizat pe puncte de atribuire, prin care se diferențiază persoanele de același sex, născute în același loc și cu aceeași dată de naștere.
 * C - Componenta C este formată dintr-o cifră de control⁠(en), care permite depistarea eventualelor erori de înlocuire sau inversare a cifrelor din componența C.N.P

## Instalare

```
$ composer require mmltech/cnp-decoder
```

## Utilizare
```php
<?php
	$cnp = new \CnpDecoder\CnpDecoder($_POST["cnp"]);
	
	echo "Vârsta: ".$cnp->Age();
	echo "<BR>Anul nașterii: ".$cnp->BirthYear();
	echo "<BR>Data nașterii timestamp: ".$cnp->BirthYearTimestamp();
	echo "<BR>Data nașterii: ".date("d M Y", $cnp->BirthYearTimestamp());
	echo "<BR>Zile rămase până la data nașterii: ".$cnp->DaysLeftUntilBirthday();
	echo "<BR>Genul: ".($cnp->Gender() == 1 ? "Masculin" : "Feminin");
	echo "<BR>Cod județ: ".$cnp->CountyCode();
	echo "<BR>Denumire județ: ".$cnp->CountyName();
	echo "<BR><BR><strong>Interpretare CNP conform clasei:</strong>";
	echo "<BR>Persoana cu CNP <b>".$_POST["cnp"]."</b> a fost a <b>".$cnp->ControlNumber()."</b>-a persoană de sex <b>".($cnp->Gender() == 1 ? "masculin" : "feminin")."</b> născută la data de <b>".date("d M Y", $cnp->BirthYearTimestamp())."</b> în județul <b>".$cnp->CountyName()."</b>";
?>
```

## Metode disponibile

```php
// Returnează anul nașterii în format AAAA
BirthYear()

// Returnează vârsta la momentul utilizării
Age()

// Returnează un timestamp aferent anului, lunii și a zilei din CNP
BirthYearTimestamp()

// Returnează un nr întreg aferent genului
Gender()

// Returnează numărul de control
ControlNumber()

// Returnează codul județului
CountyCode()

// Returnnează denumirea județului
CountyName()

// Returnează numărul de zile rămase până la data nașterii
DaysLeftUntilBirthday()

```
