
<?php
echo "<pre>"; 
require_once("class.SimpleOCR.php");

$ocr = new SimpleOCR("avito.font");
$text = $ocr->execute($_SERVER["DOCUMENT_ROOT"]."testimg/e9721dbec6d15a3146f6cbc95fcb54ea.png");

print_r($text);
echo "</pre>";