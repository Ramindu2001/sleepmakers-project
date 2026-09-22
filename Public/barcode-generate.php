<?php
require '../vendor/autoload.php';

$redColor = [0, 0, 0];
$code=$_GET["text"];

$generator = new Picqer\Barcode\BarcodeGeneratorPNG();
$barcode=file_put_contents('barcode.png', $generator->getBarcode($code, $generator::TYPE_CODE_128, 3, 50, $redColor));

?>
<img src="barcode.png" alt="">