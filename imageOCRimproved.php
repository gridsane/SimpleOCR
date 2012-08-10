
<?php

$global_start_time = microtime(true);

function get4info(&$array) {
    $conf = array(0,0,0,0);
    $w = count($array);
    $h = count($array[0]);
    $mw = round($w/2);
    $mh = round($h/2);
    for($x = 0; $x < $w; $x++) {
        for($y = 0; $y < $h; $y++) {
            $row = $array[$x][$y] ? 0 : 1;
            // top-left
            if($x <= $mw && $y < $mh) {
                $conf[0] += $row;
            }
            // top-right
            if($x > $mw && $y <= $mh) {
                $conf[1] += $row;
            }
            // bottom-left
            if($x < $mw && $y >= $mh) {
                $conf[2] += $row;
            }
            // bottom-right
            if($x >= $mw && $y > $mh) {
                $conf[3] += $row;
            }
        }
    }
    return $conf;
}

function diff (&$array1, &$array2) {
    $l = count($array1);
    $result = 0;
    for ($i=0; $i < $l; $i++) { 
        if(isset($array2[$i])) {
            $result += abs($array1[$i] - $array2[$i]);
        }
    }
    return $result;
}


global $numbers;
global $confs;
$numbers = array();
for($i=0; $i < 10; $i++) {
    $array = include("./numbers/$i");
    $numbers[$i] = $array;
    $confs[$i] = get4info($array);
}
$array = include("./numbers/-");
$numbers["-"] = $array;
$confs["-"] = get4info($array);


echo "Numbers ".(microtime(true) - $global_start_time)."<br/><br/>";

function ocr (&$input) {
    global $confs;
    $diffs = array();
    $minDiff = 1000;
    $result = 0;
    foreach($confs as $NUM => $conf) {
        $diff = 0;
        $diff = diff($input, $conf);
/*        foreach($input as $colnum => $col) {
            if(count($input) != count($number)) {
                $diff += count(array_diff($col, $number[$colnum]));
            }
        }*/
        $diffs[$NUM] = $diff;
        //echo "<br/>($NUM $diff)";
        if($diff <= $minDiff) {
            $result = $NUM;
            $minDiff = $diff;
        }
        if($minDiff == 0) break;
        //echo "$NUM=$diff<br/><br/>";
    }
    // sort($diffs);
    // return key($diffs);
    echo "<td><b style='font-size:1em'>$result</b><br/><font style='font-size:0.7em'>".(100 - 0.7*$minDiff)."%</font></td>";
    return array($result, (100 - 0.7*$minDiff));

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
        echo "<br/>";
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
    echo "<img src='testimg/$image' /><br/><table><tr>";
    $numImg = array();
    $curImg = array();
    $sc = 0;
    $procents = 0;
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
                $result = ocr(get4info($curImg));
                $procents += $result[1];
                //echo $result;
                //drawnum($curImg);
                //echo "<br/><br/>";
                $curImg = array();
                $sc = 0;
            }
            //echo "separator at $x<br/>";
        }
    }
    echo "</tr></table><br/><b style='font-size:0.8em'>".(round(($procents/count($numImg))*100)/100)."%</b><br/>Time: ".(microtime(true) - $start_time)."<br/>";
    echo "<br/><br/>";
    unlink($tmpimage);
}
echo "<br/>Global time: ".(microtime(true) - $global_start_time)."<br/>";