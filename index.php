<?php
 require_once __DIR__ . '/src/DecoderEngine.php';
 require_once __DIR__ . '/src/CNPDecoder.php';
 use CnpDecoder\CNPDecoder;
?>

<h3>Clasă PHP pentru interpretarea componentelor codului numeric personal (CNP)</h3>
<p></p>
<form method="post">
    <hr>
    <input type="text" name="cnp" placeholder="Introdu un CNP" aria-label="" value="<?= isset($_POST["cnp"]) ? $_POST["cnp"] : "" ?>">
    <button type="submit">Trimite</button>
    <hr>
    <?php
        if(isset($_POST["cnp"])){
            CNPDecoder::init($_POST['cnp']);

            echo "Vârsta: ".CNPDecoder::getAge();
            echo "<BR>Anul nașterii: ".CNPDecoder::getBirthYear();
            echo "<BR>Data nașterii timestamp: ".CNPDecoder::getBirthYearTimestamp();
            echo "<BR>Data nașterii: ".date("d M Y", CNPDecoder::getBirthYearTimestamp());
            echo "<BR>Zile rămase până la data nașterii: ".CNPDecoder::getDaysLeftUntilBirthday();
            echo "<BR>Genul: ".(CNPDecoder::getGender() == 1 ? "Masculin" : "Feminin");
            echo "<BR>Cod județ: ".CNPDecoder::getCountyCode();
            echo "<BR>Denumire județ: ".CNPDecoder::getCountyName();
            echo "<BR><BR><strong>Interpretare CNP conform clasei:</strong>";
            echo "<BR>Persoana cu CNP <b>".$_POST["cnp"]."</b> a fost a <b>".CNPDecoder::getControlNumber()."</b>-a persoană de sex <b>".(CNPDecoder::getGender() == 1 ? "masculin" : "feminin")."</b> născută la data de <b>".date("d M Y", CNPDecoder::getBirthYearTimestamp())."</b> în județul <b>".CNPDecoder::getCountyName()."</b>";
        }
    ?>
</form>
