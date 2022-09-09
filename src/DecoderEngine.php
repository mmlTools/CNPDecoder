<?php namespace CnpDecoder;

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

class DecoderEngine{
    /**
     * @param $cnp
     * @return array|bool
     */
    private function Validate($cnp){
        if(strlen($cnp) != 13)
            return 100;

        if(!ctype_digit($cnp))
            return 101;

        // Componenta care reprezintă genul și secolul
        if((int)$cnp[0] >= 1 && (int)$cnp[0] <= 8)
            $this->S = $cnp[0];
        else
            return 102;
        // Componenta care reprezintă ultimele două cifre din anul nașterii
        $this->AA = $cnp[1].$cnp[2];

        // Componenta lunii nașterii
        if(intval($cnp[3].$cnp[4]) >= 1 && intval($cnp[3].$cnp[4]) <= 12)
            $this->LL = intval($cnp[3].$cnp[4]);
        else
            return 103;

        // Componenta zilei nașterii
        if(intval($cnp[5].$cnp[6]) >= 1 && intval($cnp[5].$cnp[6]) <= 31)
            $this->ZZ = intval($cnp[5].$cnp[6]);
        else
            return 104;

        // Componenta codului județului
        if(array_key_exists(intval($cnp[7].$cnp[8]), $this->judete))
            $this->JJ = intval($cnp[7].$cnp[8]);
        else
            return 105;

        // Componenta secvențială prin care se diferențiază persoanele de același sex, născute în același loc și cu aceeași dată de naștere.
        $this->NNN = $cnp[9].$cnp[10].$cnp[11];

        // Componenta care permite depistarea eventualelor erori de înlocuire sau inversare a cifrelor din componența C.N.P
        $this->C = $cnp[12];
        return 200;
    }

    /**
     * Anul nașterii pe baza componentei S care determină secolul
     * @return int
     */
    protected function BirthYear(){
        switch ($this->S){
            case 1:
            case 2:
                return 1900 + intval($this->AA);
            case 3:
            case 4:
                return 1800 + intval($this->AA);
                break;
            case 5:
            case 6:
                return 2000 + intval($this->AA);
                break;
            default:
                return 0;
        }
    }

    /**
     * Vârsta pe baza datei formatate din CNP
     * @return int
     */
    protected function Age(){
        $an = $this->BirthYear();

        return (date("md", date("U", mktime(0, 0, 0, $this->LL, $this->ZZ, $an))) > date("md")
            ? ((date("Y") - $an) - 1)
            : (date("Y") - $an));
    }

    /**
     * Returnează un timestamp aferent datei nașterii din CNP
     * @return false|int
     */
    protected function BirthYearTimestamp(){
        return strtotime($this->BirthYear().'/'.$this->LL.'/'.$this->ZZ);
    }

    /**
     * Genul pe baza componentei S care determina genul/sexul
     * 0 - nedefinit, situatie exceptională care nu are voie să apară
     * 1 - Masculin
     * 2 - Feminin
     * @return int
     */
    protected function Gender(){
        switch ($this->S){
            case 1:
            case 3:
            case 5:
            case 7:
                return 1;
                break;
            case 2:
            case 4:
            case 6:
            case 8:
                return 2;
                break;
            default:
                return 0;
        }
    }

    /**
     * @return mixed
     */
    protected function ControlNumber(){
        return $this->NNN;
    }

    /**
     * @return mixed
     */
    protected function CountyCode(){
        return $this->JJ;
    }

    /**
     * Avem deja o validare în acest sens deci returnăm direct denumirea județului
     * @return mixed
     */
    protected function CountyName(){
        return $this->judete[$this->JJ];
    }

    /**
     * @return string
     */
    protected function DaysLeftUntilBirthday(){
        $datetime1 = date_create(date("Y").'-'.$this->LL.'-'.$this->ZZ);
        $datetime2 = date_create(date("Y-m-d"));
        $interval = date_diff($datetime1, $datetime2);
        return $interval->days;
    }

    // Componentele CNP-ului
    protected $S, $AA, $LL, $ZZ, $JJ, $NNN, $C;

    // Variabila care stochează erorile de validare
    // Implicit are valoarea NULL
    protected $validator;

    // O listă cu județele și codurile aferente componentei JJ
    protected $judete = [
        1 => 'Alba',
        2 => 'Arad',
        3 => 'Argeș',
        4 => 'Bacău',
        5 => 'Bihor',
        6 => 'Bistrița-Năsăud',
        7 => 'Botoșani',
        8 => 'Brașov',
        9 => 'Brăila',
        10 => 'Buzău',
        11 => 'Caraș-Severin',
        12 => 'Cluj',
        13 => 'Constanța',
        14 => 'Covasna',
        15 => 'Dâmbovița',
        16 => 'Dolj',
        17 => 'Galați',
        18 => 'Gorj',
        19 => 'Harghita',
        20 => 'Hunedoara',
        21 => 'Ialomița',
        22 => 'Iași',
        23 => 'Ilfov',
        24 => 'Maramureș',
        25 => 'Mehedinți',
        26 => 'Mureș',
        27 => 'Neamț',
        28 => 'Olt',
        29 => 'Prahova',
        30 => 'Satu Mare',
        31 => 'Sălaj',
        32 => 'Sibiu',
        33 => 'Suceava',
        34 => 'Teleorman',
        35 => 'Timiș',
        36 => 'Tulcea',
        37 => 'Vaslui',
        38 => 'Vâlcea',
        39 => 'Vrancea',
        40 => 'București',
        41 => 'București - Sector 1',
        42 => 'București - Sector 2',
        43 => 'București - Sector 3',
        44 => 'București - Sector 4',
        45 => 'București - Sector 5',
        46 => 'București - Sector 6',
        51 => 'Călărași',
        52 => 'Giurgiu',
        47 => 'Bucuresti - Sector 7 (desfiintat)',
        48 => 'Bucuresti - Sector 8 (desfiintat)'
    ];

    /**
     * CNPDecoder constructor.
     * @param $cnp
     */
    public function __construct($cnp)
    {
        // Se pot adăuga validări ulterioare pentru componentele de timp/dată
       try{
           $this->validator = $this->Validate($cnp);
           if($this->validator != 200)
               throw new CNPExceptions(null, $this->validator);

       } catch (CNPExceptions $e){
           // În funție de necesitate schimbați aici GetErrorMessage() cu GetErrorCode()
           // de asemenea aveți aici și o listă cu posibile metode de returnare a valorilor
           // return $e->GetErrorMessage()
           // return $e->GetErrorCode()
           // return {$e->GetErrorMessage(), $e->GetErrorCode()];
           // return {"message" => $e->GetErrorMessage(), "code" => $e->GetErrorCode()];
           exit($e->GetErrorMessage());
       }
    }
}

/**
 * Class CNPExceptions
 * Am decis să mut exceptiile întro clasă separată
 * pentru a putea fii accesate și modificate mai
 * ușor
 */
class CNPExceptions extends \Exception {
    const ERROR_100 = 'Număr invalid de caractere',
          ERROR_101 = 'CNP-ul poate conține numai cifre fără spații libere',
          ERROR_102 = 'Componenta care reprezintă sexul și secolul în care s-a născut persoana nu este validă',
          ERROR_103 = 'Câmpul aferent lunii are o valoare invalidă',
          ERROR_104 = 'Câmpul aferent zilei are o valoare invalidă',
          ERROR_105 = 'Cod județ invalid',
          ERROR_200 = 'OK';

    public function GetErrorMessage(){
        $error_msg = "self::ERROR_".$this->getCode();
        return constant($error_msg);
    }

    public function GetErrorCode(){
        return $this->getCode();
    }
}