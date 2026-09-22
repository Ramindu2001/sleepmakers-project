<?php 
$name="products_sample";
$filename = $name.".csv";
header("Content-Type: text/csv; charset=UTF-16LE");
header("Content-Disposition: attachment;filename=$filename");
echo file_get_contents("../Assets/csv/".$filename);
readfile($filename);
exit()
?>