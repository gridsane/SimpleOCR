
<?php
/**
 * Simple OCR class.
 * Recognize a text, based on known font.
 * @author gridsane <gridsane@gmail.com>
 * @license http://www.gnu.org/licenses/gpl.html
 * @version 0.1
 */
/**
 * @todo iteration v0.2 features:
 * - explodeLines body (with avito font tests)
 * 
 * @todo iteration v0.3 features:
 * - automatic teach
 * - slando font
 * - Exceptions
 *
 * @todo sometime
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
    private function explodeLines (&$imageArray)
    {
        // @todo реализация
        return array($imageArray);
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
            if ($isVertLine) {
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
            if ($minDiff < 0 || $minDiff > $diff) {
                $minDiff = $diff;
                $result = $char;
                if ($diff == 0) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Convert image to string, based on preload "font" patterns
     * @param  string $imagePath path to target image
     * @return string recognized text (if there are many lines in image, it also has new lines)
     */
    public function execute ($imagePath)
    {
        $image = $this->imageSharp($imagePath);
        $imageArray = $this->imageToArray($image);
        $text = "";
        $lines = $this->explodeLines($imageArray);
        foreach ($lines as $line) {
            $chars = $this->explodePattern($line);
            foreach ($chars as $char) {
                $text .= $this->recognize($char);
            }
            // @todo fix last line emptiness
            $text .= "\n";
        }

        return $text;
    }
}
