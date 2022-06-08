<?php require_once 'CNPDecoder.php'; ?>

<h3>Clasă PHP pentru interpretarea componentelor codului numeric personal (CNP)</h3>
<p></p>
<form method="post">
    <hr>
    <input type="text" name="cnp" placeholder="Introdu un CNP" aria-label="" value="<?= isset($_POST["cnp"]) ? $_POST["cnp"] : "" ?>">
    <button type="submit">Trimite</button>
    <hr>
    <?php
    $cnp = new CNPDecoder($_POST["cnp"]);

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
</form>
