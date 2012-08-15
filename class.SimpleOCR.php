
<?php
/**
 * Simple OCR class.
 * Recognize a text, based on known font.
 * @author gridsane <gridsane@gmail.com>
 * @license http://www.gnu.org/licenses/gpl.html
 * @version 0.3
 */

/**
 * @todo v0.4
 * - smart teach (beautify exists implementation)
 * - add class settings (accuracy, new lines and etc.)
 * 
 * @todo sometime
 * - optimization!?
 * - Exceptions
 * - improve patternDiff, may be optionally
 * - do not use convert, or make it optionally
 */

class SimpleOCR
{
    public $font;
    private $text;

    /**
     * Initialize class with font
     * @param mixed $font array or path to file returns array
     */
    public function __construct ($font = null)
    {
        if (is_array($font)) {
            $this->font = $font;
        } elseif (is_string($font) && is_file($font)) {
            $this->font = include $font;
        }
    }

    /**
     * Sharps the image, for good color recognition
     * @param  string $imagePath
     * @return string path to temp image
     */
    private function imageSharp (&$imagePath)
    {
        $tempImage = tempnam(sys_get_temp_dir(), 'imageocr');
        $code = 0;
        system("convert {$imagePath} -threshold 70% -trim {$tempImage}", $code);
        // @todo exception if code != 0
        return $tempImage;
    }

    /**
     * Convert image to the 2d array of colors (0 or 1 is ideal)
     * @param  string $imagePath
     * @return array
     */
    private function imageToArray (&$imagePath)
    {
        $image = imagecreatefrompng($imagePath);
        $imageArray = array(array());
        $width = imagesx($image);
        $height = imagesy($image);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $imageArray[$x][$y] = (int) imagecolorat($image, $x, $y);
            }
        }

        return $imageArray;
    }

    /**
     * Explode array to non-empty lines
     * @param  array $imageArray array of colors (0 or 1 is ideal)
     * @return array trimmed lines
     */
    private function explodeLines (&$imageArray, $accuracy=0)
    {
        // @todo optimization!
        $lines = array();
        $width = count($imageArray);
        $height = count($imageArray[0]);
        $lastY = 0;
        for ($y = 0; $y < $height; $y++) { 
            $curLinePixels = 0;
            $isEmptyLine = true;
            for ($x = 0; $x < $width; $x++) { 
                $color = $imageArray[$x][$y];
                if($color == 0) {
                    if($curLinePixels < $accuracy) {
                        $curLinePixels++;
                    } else {
                        $isEmptyLine = false;
                        break;
                    }
                }
            }
            if($isEmptyLine || ($y+1) == $height) {
                // don't ignore last non-empty line
                $maxY = (($y+1) == $height) ? $height : $y;
                $part = array(array());
                for ($dx = 0; $dx < $width; $dx++) {
                    for ($dy = $lastY, $ay = 0; $dy < $maxY; $dy++, $ay++) {
                        $part[$dx][$ay] = $imageArray[$dx][$dy];
                    }
                }
                if(($y - $lastY) >= 1) {
                    $lines[] = $part;
                }
                $lastY = $y+1;
            }
        }
        return $lines;
    }

    /**
     * Convert a single-character color array to pattern.
     * Method divides array into 4 areas top-left, top-right
     * and so on. Then it counts pixels in each area. This
     * pattern is unique for each character.
     * @param  array $character array of colors
     * @return array calculated pattern
     */
    private function getPattern (&$character)
    {
        $pattern = array(0,0,0,0);
        $w = count($character);
        $h = count($character[0]);
        $mw = round($w/2);
        $mh = round($h/2);
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $row = $character[$x][$y] ? 0 : 1;
                // top-left
                if ($x <= $mw && $y < $mh) {
                    $pattern[0] += $row;
                }
                // top-right
                if ($x > $mw && $y <= $mh) {
                    $pattern[1] += $row;
                }
                // bottom-left
                if ($x < $mw && $y >= $mh) {
                    $pattern[2] += $row;
                }
                // bottom-right
                if ($x >= $mw && $y > $mh) {
                    $pattern[3] += $row;
                }
            }
        }

        return $pattern;
    }

    /**
     * Explodes line to an array of character patterns
     * @param  array $lineArray single-line array
     * @return array patterns
     */
    private function explodePattern (&$lineArray)
    {
        // @todo считать количество разделителей
        // и если нужно ставить пробелы
        $chars = array();
        $width = count($lineArray);
        $height = count($lineArray[0]);
        $currentChar = array();
        for ($x = 0; $x < $width; $x++) {
            $isVertLine = true;
            for ($y = 0; $y < $height; $y++) {
                if ($lineArray[$x][$y] == 0) {
                    $isVertLine = false;
                    break;
                }
            }
            if ($isVertLine || ($x+1) == $width) {
                if(($x+1) == $width && $isVertLine == false) {
                    $currentChar[] = $lineArray[$x];
                }
                if (count($currentChar) > 0) {
                    $chars[] = $this->getPattern($currentChar);
                }
                $currentChar = array();
            } else {
                $currentChar[] = $lineArray[$x];
            }
        }

        return $chars;
    }

    /**
     * Explodes line to an array of character
     * @param  array $lineArray single-line array
     * @return array patterns
     */
    private function explodeChars (&$lineArray)
    {
        // @todo считать количество разделителей
        // и если нужно ставить пробелы
        $chars = array();
        $width = count($lineArray);
        $height = count($lineArray[0]);
        $currentChar = array();
        for ($x = 0; $x < $width; $x++) {
            $isVertLine = true;
            for ($y = 0; $y < $height; $y++) {
                if ($lineArray[$x][$y] == 0) {
                    $isVertLine = false;
                    break;
                }
            }
            if ($isVertLine || ($x+1) == $width) {
                if(($x+1) == $width && $isVertLine == false) {
                    $currentChar[] = $lineArray[$x];
                }
                if (count($currentChar) > 0) {
                    $chars[] = $currentChar;
                }
                $currentChar = array();
            } else {
                $currentChar[] = $lineArray[$x];
            }
        }

        return $chars;
    }

    /**
     * Calculate the difference between two patterns
     * 0 - is no differnce and so on
     * @param  array   $pattern1
     * @param  array   $pattern2
     * @return integer
     */
    private function patternDiff (&$pattern1, &$pattern2)
    {
        $result = 0;
        for ($i = 0; $i < 4; $i++) {
            if (isset($pattern2[$i])) {
                $result += abs($pattern1[$i] - $pattern2[$i]);
            }
        }
        return $result;
    }

    /**
     * Recognize pattern
     * (counts smallest difference between font patterns)
     * @param  array  $targetPattern pattern to recognize
     * @return string a single pattern-based character
     */
    private function recognize (&$targetPattern)
    {
        $minDiff = -1;
        $result = '';
        foreach ($this->font as $char => $pattern) {
            $diff = $this->patternDiff($targetPattern, $pattern);
            if ($minDiff < 0 || $minDiff >= $diff) {
                $minDiff = $diff;
                $result = $char;
                if ($diff == 0) {
                    break;
                }
            }
        }
        if($minDiff > 2) {
            print_r($targetPattern);
            $result = "";
        }
        // if($minDiff != 0) echo "\n(($minDiff)) ".print_r($targetPattern)."\n";
        return $result;
    }

    /**
     * Convert image to string, based on preload "font" patterns
     * @param  string  $imagePath    path to target image
     * @param  boolean $trim         trim each character for more clever work (slower)
     * @param  boolean $skipNewLines draw \n after each line
     * @return string recognized text
     */
    public function execute ($imagePath, $trim=false, $skipNewLines=true)
    {
        $image = $this->imageSharp($imagePath);
        $imageArray = $this->imageToArray($image);
        $text = "";
        $lines = $this->explodeLines($imageArray, 1);
        foreach ($lines as $line) {
            $chars = $this->explodeChars($line);
            foreach ($chars as $char) {
                if($trim) {
                    $trimmedChar = $this->explodeLines($char);
                    if(count($trimmedChar) == 0) {
                        continue;
                    }
                    $char = $trimmedChar[0];
                }
                $char = $this->getPattern($char);
                $text .= $this->recognize($char);
            }
            if(!$skipNewLines) {
                $text .= "\n";
            }
        }
        return rtrim($text);
    }

    /**
     * Saves font array to file
     * @param  array  $font     array of font patterns
     * @param  string $fontFile target file, if exists, merge with $font var
     * @return boolean          file_put_contents result
     */
    private function saveFont ($font, $fontFile="default.font")
    {
        if(is_file($fontFile)) {
            $fontFileArray = include($fontFile);
        } else {
            $fontFileArray = array();
        }
        // merge with already saved font
        $fontNewArray = array_merge($fontFileArray, $font);
        $fontExport = var_export($fontNewArray, true);
        // ut8 entities fix
        $fontExport = mb_convert_encoding($fontExport, "UTF-8", "HTML-ENTITIES");
        // clean whitespace and unnessesary symbols
        $fontExport = preg_replace("/\s+/", "", $fontExport);
        $fontExport = preg_replace("/,\)/", ")", $fontExport);
        $fontExport = preg_replace("/\d=>(\d+)/", "\\1", $fontExport);
        $fontExport = preg_replace("/(\d+)=>array/", "'\\1'=>array", $fontExport);
        $fontExport = "<?php return ".$fontExport.";";
        return file_put_contents($fontFile, $fontExport);
    }

    /**
     * Per-pixel draws character (ASCII or smthg)
     * @param  array  $char  character array
     * @param  string $white represents whitespace (background of character)
     * @param  string $black represents dark non-empty pixels
     * @return void
     */
    private function drawChar ($char, $white="&nbsp", $black="<b>&diams;</b>")
    {
        $w = count($char);
        $h = count($char[0]);
        echo "<pre style='font-size: 0.5em;'>";
        for($y = 0; $y < $h; $y++) {
            for($x = 0; $x < $w; $x++) {
                $c = $char[$x][$y];
                echo $c ? "$white" : "$black";
            }
            echo "<br/>";
        }
        echo "</pre>";
    }

    /**
     * @todo   DIRTY function, think about clever teach realization
     * Traces the image and waiting for POST with 
     * human-recognized characters. If POST exists,
     * copy calculated patterns to $fontFile
     * @param  string $imagePath
     * @param  string $fontFile  file to store font data
     * @return void
     */
    public function teach ($imagePath, $fontFile="default.font")
    {
        $image = $this->imageSharp($imagePath);
        $imageArray = $this->imageToArray($image);
        $lines = $this->explodeLines($imageArray, 1);
        $chars = array();
        $font = array();
        echo "<form method='POST'>";
        echo "<table>";
        echo "<input type=submit />";
        foreach ($lines as $lkey => $line) {
            $characters = $this->explodeChars($line);
            foreach ($characters as $ckey => $char) {
                $trimmedChar = $this->explodeLines($char);
                $char = $trimmedChar[0];
                if(isset($_POST['char'])) {
                    if(!empty($_POST['char'][$lkey][$ckey])) {
                        $fontChar = $_POST['char'][$lkey][$ckey];
                        $font[$fontChar] = $this->getPattern($char);
                    }
                } else {
                    $chars[] = $char;
                    echo "<tr><td style='border:1px solid #ccc; padding:10px'>";
                    $this->drawChar($char);
                    echo "</td><td>"; 
                    echo "<input type='text' name='char[$lkey][$ckey]' />";
                    echo "</td></tr>";
                }
            }
        }
        echo "</table>";
        echo "<input type=submit />";
        echo "</form>";
        if(isset($_POST['char'])) {
            $this->saveFont($font, $fontFile);
        }
    }

}
