<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Identity\Mrz\MrzDecoder;
use Identity\Cnp\CnpDecoder;

$mrz = "IDROUXXXXX<<XXXXX<<<<<<<<<<<<<<<<<<<SX000000<1ROU0000007M260000410000006";

$result = MrzDecoder::decode($mrz, false);
echo "<pre>", print_r($result), "</pre>";

$cnp = new CnpDecoder($result['romania']['cnp_reconstruit']['cnp']);
echo "<pre>", print_r($cnp->toArray()), "</pre>";
