
<?php
echo "<pre>"; 
require_once("class.SimpleOCR.php");

$ocr = new SimpleOCR ("slando.font");
for ($i=0; $i < 14; $i++) { 
    $text = $ocr->execute($_SERVER["DOCUMENT_ROOT"]."testimg/slando/$i.png", true);
    echo "<img src='testimg/slando/$i.png' /><br/>\n";
    echo $text."\n\n\n";
}

echo "</pre>";