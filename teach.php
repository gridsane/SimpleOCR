
<?php
echo "<pre>";
require_once("class.SimpleOCR.php");
$ocr = new SimpleOCR ();
for ($i=0; $i < 14; $i++) { 
    $ocr->teach($_SERVER["DOCUMENT_ROOT"]."testimg/slando/$i.png");
}