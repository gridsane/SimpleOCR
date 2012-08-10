<pre>
<?php
$global_start_time = microtime(true);

global $numbers;
$numbers = array();
for($i=0; $i < 10; $i++) {
    $array = include("./numbers/$i");
    $numbers[$i] = $array;
}
$array = include("./numbers/-");
$numbers["-"] = $array;

function arrayRecursiveDiff($aArray1, $aArray2) { 
    $aReturn = array(); 

    foreach ($aArray1 as $mKey => $mValue) { 
        if (array_key_exists($mKey, $aArray2)) { 
            if (is_array($mValue)) { 
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]); 
                if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; } 
            } else { 
                if ($mValue != $aArray2[$mKey]) { 
                    $aReturn[$mKey] = $mValue; 
                } 
            } 
        } else { 
            $aReturn[$mKey] = $mValue; 
        } 
    } 

    return $aReturn; 
} 

function ocr ($input) {
    global $numbers;
    $diffs = array();
    $minDiff = 1000;
    $result = 0;
    foreach($numbers as $NUM => $number) {
        $diff = 0;
        $diff = count(arrayRecursiveDiff($input, $number));
/*        foreach($input as $colnum => $col) {
            if(count($input) != count($number)) {
                $diff += count(array_diff($col, $number[$colnum]));
            }
        }*/
        $diffs[$NUM] = $diff;
        if($diff <= $minDiff) {
            $result = $NUM;
            $minDiff = $diff;
        }
        if($minDiff == 0) break;
        //echo "$NUM=$diff\n\n";
    }
    // sort($diffs);
    // return key($diffs);
    return $result;

}

// ocr($numbers[3]);

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

$imgdir = $_SERVER["DOCUMENT_ROOT"]."testimg/";

$images = scandir($imgdir);
foreach($images as $image) {
    if($image == "." || $image == ".." || $image == "tempimg.png") continue;
    $start_time = microtime(true);
    // открываем изображение
    $targetimage = $imgdir.$image;
    $tmpimage = $imgdir."tempimg.png";
    system("convert $targetimage -threshold 70% -trim $tmpimage", $res);
    $img = imagecreatefrompng($tmpimage);
    $w = imagesx($img);
    $h = imagesy($img);
    echo "<img src='testimg/$image' />\n";
    $numImg = array();
    $curImg = array();
    $sc = 0;
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
            $sc++;
            if($sc > 5) {
                echo "&nbsp;";
                $sc = 0;
            }
            // надо все что до
            if(!empty($curImg)) {
                $numImg[] = $curImg;
                $result = ocr($curImg);
                echo $result;
                //drawnum($curImg);
                //echo "\n\n";
                $curImg = array();
                $sc = 0;
            }
            //echo "separator at $x\n";
        }
    }
    echo "\nTime: ".(microtime(true) - $start_time)."\n";
    echo "\n\n";
    unlink($tmpimage);
}
echo "\nGlobal time: ".(microtime(true) - $global_start_time)."\n";