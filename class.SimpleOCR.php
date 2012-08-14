
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

    private function imageSharp (&$imagePath)
    {
        $tempImage = tempnam(sys_get_temp_dir(), 'imageocr');
        $code = 0;
        system("convert {$imagePath} -threshold 70% -trim {$tempImage}", $code);
        // TODO: exception if code != 0
        return $tempImage;
    }

    private function imageToArray (&$imagePath)
    {
        $image = imagecreatefrompng($imagePath);
        $imageArray = array(array());
        $width = imagesx($image);
        $height = imagesy($image);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $imageArray[$x][$y] = (int)imagecolorat($image, $x, $y); 
            }
        }
        return $imageArray;
    }
 
    private function explodeLines (&$imageArray) {
        // TODO: реализация
        return array($imageArray);
    }

    private function explodePattern (&$lineArray) {
        // TODO: считать количество разделителей
        // и если нужно ставить пробелы
        $chars = array();
        $width = count($lineArray);
        $height = count($lineArray[0]);
        $currentChar = array();
        for ($x = 0; $x < $width; $x++) {
            $isVertLine = true;
            for($y = 0; $y < $height; $y++) {
                if($lineArray[$x][$y] == 0) {
                    $isVertLine = false;
                    break;
                }
            }
            if($isVertLine) {
                if(count($currentChar) > 0) {
                    $chars[] = $this->getPattern($currentChar);
                }
                $currentChar = array();
            } else {
                $currentChar[] = $lineArray[$x];
            }
        }
        return $chars;
    }

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

    private function recognize (&$targetPattern)
    {
        $minDiff = -1;
        $result = '';
        foreach ($this->font as $char => $pattern) {
            $diff = $this->patternDiff($targetPattern, $pattern);
            if($minDiff < 0 || $minDiff > $diff) {
                $minDiff = $diff;
                $result = $char;
                if($diff == 0) {
                    break;
                }
            }
        }
        return $result;
    }

    public function execute ($imagePath)
    {
        $image = $this->imageSharp($imagePath);
        $imageArray = $this->imageToArray($image);
        $text = "";
        $lines = $this->explodeLines($imageArray);
        foreach($lines as $line) {
            $chars = $this->explodePattern($line);
            foreach($chars as $char) {
                $text .= $this->recognize($char);
            }
            $text .= "\n";
        }
        return $text;
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
}
