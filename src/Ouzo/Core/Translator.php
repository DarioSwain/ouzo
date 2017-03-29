<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo;

use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Strings;

class Translator
{
    private $_labels;
    private $language;
    private $pseudoLocalizationEnabled;

    public function __construct($language, $labels)
    {
        $this->_labels = $labels;
        $this->language = $language;
        $this->pseudoLocalizationEnabled = Config::getValue('pseudo_localization') ? true : false;
    }

    public function translate($key, $params = [])
    {
        $explodedKey = explode('.', $key);
        $translation = Arrays::getNestedValue($this->_labels, $explodedKey) ?: $key;
        return $this->localize(Strings::sprintAssoc($translation, $params));
    }

    public function translateWithChoice($key, $choice, $params = [])
    {
        $value = $this->translate($key, $params);

        $split = explode('|', $value);
        $index = $this->getIndex($choice);
        if ($index >= sizeof($split)) {
            $index = sizeof($split) - 1;
        }
        return $this->localize($split[$index]);
    }

    private function localize($text)
    {
        return $this->pseudoLocalizationEnabled ? $this->pseudoLocalize($text) : $text;
    }

    private function pseudoLocalize($text)
    {
        if (is_array($text)) {
            $array = $text;
            foreach ($array as $key => $value) {
                $array[$key] = is_array($value) ? $this->pseudoLocalize($value) : $this->pseudoLocalizeText($value);
            }
            return $array;
        }
        return $this->pseudoLocalizeText($text);
    }

    private function pseudoLocalizeText($text)
    {
        return $this->strtr_utf8($text,
            "abcdefghijklmnoprstuvwyzABCDEFGHIJKLMNOPRSTUVWYZ",
            "ȧƀƈḓḗƒɠħīĵķŀḿƞǿƥřşŧŭṽẇẏzȦƁƇḒḖƑƓĦĪĴĶĿḾȠǾƤŘŞŦŬṼẆẎẐ"
        );
    }

    private function strtr_utf8($text, $from, $to)
    {
        $keys = [];
        $values = [];
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        $mapping = array_combine($keys[0], $values[0]);
        return strtr($text, $mapping);
    }

    /*
     * The plural rules are derived from code of the Zend Framework (2010-09-25),
     * which is subject to the new BSD license (http://framework.zend.com/license/new-bsd).
     * Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
     *
     * This method was taken from Symfony2 framework. Copyright (c) 2004-2014 Fabien Potencier.
     */
    public function getIndex($number)
    {
        switch ($this->language) {
            case 'bo':
            case 'dz':
            case 'id':
            case 'ja':
            case 'jv':
            case 'ka':
            case 'km':
            case 'kn':
            case 'ko':
            case 'ms':
            case 'th':
            case 'tr':
            case 'vi':
            case 'zh':
                return 0;
            case 'af':
            case 'az':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'zu':
                return ($number == 1) ? 0 : 1;
            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'xbr':
            case 'ti':
            case 'wa':
                return (($number == 0) || ($number == 1)) ? 0 : 1;
            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
            case 'cs':
            case 'sk':
                return ($number == 1) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);
            case 'ga':
                return ($number == 1) ? 0 : (($number == 2) ? 1 : 2);
            case 'lt':
                return (($number % 10 == 1) && ($number % 100 != 11)) ? 0 : ((($number % 10 >= 2) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
            case 'sl':
                return ($number % 100 == 1) ? 0 : (($number % 100 == 2) ? 1 : ((($number % 100 == 3) || ($number % 100 == 4)) ? 2 : 3));
            case 'mk':
                return ($number % 10 == 1) ? 0 : 1;
            case 'mt':
                return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 1) && ($number % 100 < 11))) ? 1 : ((($number % 100 > 10) && ($number % 100 < 20)) ? 2 : 3));
            case 'lv':
                return ($number == 0) ? 0 : ((($number % 10 == 1) && ($number % 100 != 11)) ? 1 : 2);
            case 'pl':
                return ($number == 1) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);
            case 'cy':
                return ($number == 1) ? 0 : (($number == 2) ? 1 : ((($number == 8) || ($number == 11)) ? 2 : 3));
            case 'ro':
                return ($number == 1) ? 0 : ((($number == 0) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);
            case 'ar':
                return ($number == 0) ? 0 : (($number == 1) ? 1 : (($number == 2) ? 2 : ((($number % 100 >= 3) && ($number % 100 <= 10)) ? 3 : ((($number % 100 >= 11) && ($number % 100 <= 99)) ? 4 : 5))));
        }
        return 0;
    }
}
