
<?php
/**
 * Simple OCR class.
 * Recognize a text, based on known font.
 * @author gridsane <gridsane@gmail.com>
 * @license http://www.gnu.org/licenses/gpl.html
 * @version 0.1
 */

/**
 * Usage:
 * 
 * $ocr = new SimpleOCR (font);
 *
 * Обучить новому тексту (возвращает массив,
 * надо подумать, как будет происходить взаимодействие с пользователем)
 * $array = $ocr->teach("image.png");
 *
 * Запустить рапознавание, вывод - текст с переносом строк, где нужно
 * $text = $ocr->execute("image.png");
 *
 */

class SimpleOCR
{
    public $font;
    private $text;
    private $tmpImage = "simpleocr.tmp";

    /**
     * Initialize class with font
     * @param  mixed $font array or path to file returns array
     */
    public function __construct ($font = null)
    {
        if (is_array($font)) {
            $this->font = $font;
        } elseif (is_string($font) && is_file($font)) {
            $this->font = include $font;
        }
    }

    public function execute ($pathToImage)
    {
        $dir = $_SERVER["DOCUMENT_ROOT"]."testimg/";
        $tmpImage = uniqid().$this->tmpImage;
        system("convert {$dir}{$pathToImage} -threshold 70% -trim {$dir}{$tmpImage}", $res);
        var_dump($res);
        $image = imagecreatefrompng($dir.$tmpImage);
        $width = imagesx($image);
        $height = imagesy($image);

        $characters = array();
        $curChar = array();

        $procents = 0;

        for ($x = 0; $x < $width; $x++) {
            $vLine = true;
            $curColumn = array();
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                if ($color == 0) {
                    $vLine = false;
                }
                $curColumn[] = $color;
            }
            if (!$vLine || ($x+1) == $width) {
                $curChar[] = $curColumn;
                $curColumn = array();
            }
            if ($vLine || ($x+1) == $width) {

                if (!empty($curChar)) {
                    $characters[] = $curChar;
                    $result = $this->ocr($this->getPattern($curChar));
                    echo "<pre>"; print_r($this->getPattern($curChar)); echo "</pre>";
                    $procents += $result[1];
                    $curChar = array();
                    $sc = 0;
                }
            }
        }
        unlink($dir.$tmpImage);
    }


    private function getPattern (&$character)
    {
        $conf = array(0,0,0,0);
        $w = count($character);
        $h = count($character[0]);
        $mw = round($w/2);
        $mh = round($h/2);
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $row = $character[$x][$y] ? 0 : 1;
                // top-left
                if ($x <= $mw && $y < $mh) {
                    $conf[0] += $row;
                }
                // top-right
                if ($x > $mw && $y <= $mh) {
                    $conf[1] += $row;
                }
                // bottom-left
                if ($x < $mw && $y >= $mh) {
                    $conf[2] += $row;
                }
                // bottom-right
                if ($x >= $mw && $y > $mh) {
                    $conf[3] += $row;
                }
            }
        }
        return $conf;
    }

    private function diff (&$array1, &$array2)
    {
        $l = count($array1);
        $result = 0;
        for ($i = 0; $i < $l; $i++) {
            if (isset($array2[$i])) {
                $result += abs((int)$array1[$i] - (int)$array2[$i]);
            }
        }
        return $result;
    }

    private function ocr (&$input)
    {
        $diffs = array();
        $minDiff = 1000;
        $result = 0;
        foreach ($this->font as $NUM => $conf) {
            $diff = 0;
            $diff = $this->diff($input, $conf);
            $diffs[$NUM] = $diff;
            if ($diff <= $minDiff) {
                $result = $NUM;
                $minDiff = $diff;
            }
            if ($minDiff == 0) {
                break;
            }
        }
        echo "<td><b style = 'font-size:1em'>$result</b><br/><font style = 'font-size:0.7em'>".(100 - 0.7*$minDiff)."%</font></td>";
        return array($result, (100 - 0.7*$minDiff));
    }
}
