<?php
namespace Identity\Cnp;

final class CnpException extends \Exception
{
    public const ERROR_100 = 'Număr invalid de caractere';
    public const ERROR_101 = 'CNP-ul poate conține numai cifre fără spații libere';
    public const ERROR_102 = 'Componenta S (sex + secol) este invalidă';
    public const ERROR_103 = 'Luna are o valoare invalidă';
    public const ERROR_104 = 'Ziua are o valoare invalidă';
    public const ERROR_105 = 'Cod județ invalid';
    public const ERROR_106 = 'Cifra de control este invalidă';
    public const ERROR_200 = 'OK';

    public function message(): string
    {
        $const = __CLASS__.'::ERROR_'.$this->getCode();
        return \defined($const) ? \constant($const) : 'Eroare necunoscută';
    }
}
