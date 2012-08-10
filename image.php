<pre>
<?php
function exportarray ($file, $array) {
    file_put_contents("./numbers/exportnum".$file, var_export($array, true));
}

function drawnum ($array) {
    $w = count($array);
    $h = count($array[0]);
    echo "<pre>";
    for($y = 0; $y < $h; $y++) {
        for($x = 0; $x < $w; $x++) {
            $c = $array[$x][$y];
            echo $c ? "&nbsp;" : "+";
        }
        echo "\n";
    }
    echo "</pre>";
}
// открываем изображение
$img = imagecreatefrompng('test5.png');
$w = imagesx($img);
$h = imagesy($img);

$numImg = array();
$curImg = array();
for ($x=0; $x < $w; $x++) {
    $sep = true; 
    $curCol = array();
    for ($y=0; $y < $h; $y++) {
        $color = imagecolorat($img, $x, $y);
        if($color == 0) {
            $sep = false;
        }
        $curCol[] = $color;
    }
    if(!$sep || ($x+1) == $w) {
        // в темп
        $curImg[] = $curCol;
        $curCol = array();
    }
    if($sep || ($x+1) == $w) {
        // надо все что до
        if(!empty($curImg)) {
            $numImg[] = $curImg;
            echo count($numImg).":::::\n";
            drawnum($curImg);
            echo "\n\n";
            exportarray(count($numImg), $curImg);
            $curImg = array();
        }
        //echo "separator at $x\n";
    }
}
