
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
    private $font;

    /**
     * Initialize class with font
     * @param  mixed $font array or path to file returns array
     */
    public function __constructor ($font)
    {
        
    }
}