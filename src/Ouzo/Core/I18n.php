<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo;

use Exception;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Files;
use Ouzo\Utilities\Path;

class I18n
{
    const DEFAULT_LANGUAGE = 'en';

    private static $_translator;
    private static $_labels;

    public static function t($key, $params = [], PluralizeOption $pluralize = null)
    {
        if (!$key) {
            return '';
        }
        if (!self::$_translator) {
            self::$_translator = self::_getTranslator();
        }
        if ($pluralize != null) {
            return self::$_translator->translateWithChoice($key, $pluralize->getValue(), $params);
        }
        return self::$_translator->translate($key, $params);
    }

    public static function reset($translator = null)
    {
        self::$_translator = $translator;
        self::$_labels = null;
    }

    public static function labels($key = '')
    {
        $labels = self::loadLabels();
        $explodedKey = explode('.', $key);
        return $key ? Arrays::getNestedValue($labels, $explodedKey) : $labels;
    }

    public static function loadLabels()
    {
        if (!self::$_labels) {
            $language = self::getLanguage();
            $path = Path::join(ROOT_PATH, 'locales', $language . '.php');
            if (!Files::exists($path)) {
                throw new Exception('Cannot find declared language file: ' . $language);
            }
            /** @noinspection PhpIncludeInspection */
            self::$_labels = require($path);
        }
        return self::$_labels;
    }

    private static function _getTranslator()
    {
        $_labels = self::loadLabels();
        return new Translator(self::getLanguage(), $_labels);
    }

    private static function getLanguage()
    {
        return Config::getValue('language') ?: I18n::DEFAULT_LANGUAGE;
    }

    public static function pluralizeBasedOn($value)
    {
        return new PluralizeOption($value);
    }
}
