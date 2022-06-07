<?php require_once 'CNPDecoder.php'; ?>

<h3>Clasă PHP pentru interpretarea componentelor codului numeric personal (CNP)</h3>
<form method="post">
<hr>
    <input type="text" name="cnp" placeholder="Introdu un CNP" aria-label="" value="<?= isset($_POST["cnp"]) ? $_POST["cnp"] : "" ?>">
<button type="submit">Trimite</button>
    <hr>
<?php
if(isset($_POST["cnp"]))
{
    $cnp = new CNPDecoder($_POST["cnp"]);

    if(!$cnp->validator)
    {
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
    } else{
        echo "Eroare implicită: <span style='color: red'>".$cnp->validator["message"]."</span><br>";
        switch ($cnp->validator["code"]){
            case 100:
                echo "Eroare customizată: <span style='color: red'>Eroare pentru număr invalid de caractere</span>";
                break;
            case 101:
                echo "Eroare customizată: <span style='color: red'>Eroare conținut/caractere în CNP</span>";
                break;
            case 102:
                echo "Eroare customizată: <span style='color: red'>Eroare componentă gen/secol invalidă</span>";
                break;
            case 103:
                echo "Eroare customizată: <span style='color: red'>Eroare câmp aferent lunii</span>";
                break;
            case 104:
                echo "Eroare customizată: <span style='color: red'>Eroare câmp aferent zilei</span>";
                break;
            case 105:
                echo "Eroare customizată: <span style='color: red'>Eroare componentă cod județ invalid</span>";
                break;
            default:
                echo "Eroare customizată: <span style='color: red'>Cod de eroare nedefinit</span>";
                break;
        }
    }
}
?>
</form>
