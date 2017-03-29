<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
use Ouzo\AutoloadNamespaces;
use Ouzo\Config;
use Ouzo\ControllerUrl;
use Ouzo\Helper\PartialTooltip;
use Ouzo\I18n;
use Ouzo\PluralizeOption;
use Ouzo\Session;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Date;
use Ouzo\Utilities\Objects;
use Ouzo\Utilities\Strings;
use Ouzo\View;

function url($params)
{
    return ControllerUrl::createUrl($params);
}

function renderWidget($widgetName)
{
    $className = ucfirst($widgetName);
    $viewWidget = new View($className . '/' . $widgetName);

    $classLoad = AutoloadNamespaces::getWidgetNamespace() . $className;
    $widget = new $classLoad($viewWidget);

    return $widget->render();
}

function renderPartial($name, array $values = [])
{
    $view = new View($name, $values);
    return PartialTooltip::wrap($view->render(), $name);
}

function addFile(array $fileInfo = [], $stringToRemove = '')
{
    if (!empty($fileInfo)) {
        $prefixSystem = Config::getValue('global', 'prefix_system');
        $suffixCache = Config::getValue('global', 'suffix_cache');
        $suffixCache = !empty($suffixCache) ? '?' . $suffixCache : '';

        $url = $prefixSystem . $fileInfo['params']['url'] . $suffixCache;
        $url = Strings::remove($url, $stringToRemove);

        return _getHtmlFileTag($fileInfo['type'], $url);
    }
    return null;
}

function addScript($url, $stringToRemove = '')
{
    return addFile(['type' => 'script', 'params' => ['url' => $url]], $stringToRemove);
}

function addLink($url, $stringToRemove = '')
{
    return addFile(['type' => 'link', 'params' => ['url' => $url]], $stringToRemove);
}

function _getHtmlFileTag($type, $url)
{
    switch ($type) {
        case 'link':
            return '<link rel="stylesheet" href="' . $url . '" type="text/css" />' . PHP_EOL;
        case 'script':
            return '<script type="text/javascript" src="' . $url . '"></script>' . PHP_EOL;
    }
    return null;
}

function showErrors(array $errors = [])
{
    if ($errors) {
        $errorView = new View('error_alert');
        $errorView->errors = $errors;
        return $errorView->render();
    }
    return null;
}

function showNotices(array $notices = [])
{
    if (Session::has('messages') || $notices) {
        $sessionMessages = Arrays::filterNotBlank(Arrays::toArray(Session::get('messages')));
        $notices = array_merge($sessionMessages, $notices);
        $noticeView = new View('notice_alert');
        $noticeView->notices = $notices;
        return $noticeView->render();
    }
    return null;
}

function showSuccess(array $notices = [])
{
    if (Session::has('messages') || $notices) {
        $sessionMessages = Arrays::filterNotBlank(Arrays::toArray(Session::get('messages')));
        $notices = array_merge($sessionMessages, $notices);
        $noticeView = new View('success_alert');
        $noticeView->notices = $notices;
        return $noticeView->render();
    }
    return null;
}

function showWarnings(array $warnings = [])
{
    if ($warnings) {
        $warningView = new View('warning_alert');
        $warningView->warnings = $warnings;
        return $warningView->render();
    }
    return null;
}

function formatDate($date, $format = 'Y-m-d')
{
    return Date::formatDate($date, $format);
}

function formatDateTime($date, $format = 'Y-m-d H:i')
{
    return Date::formatDateTime($date, $format);
}

function formatDateTimeWithSeconds($date)
{
    return Date::formatDateTime($date, 'Y-m-d H:i:s');
}

function pluralise($count, $words)
{
    return $words[$count == 1 ? 'singular' : 'plural'];
}

function t($textKey, $params = [], PluralizeOption $pluralize = null)
{
    return I18n::t($textKey, $params, $pluralize);
}

function toString($object)
{
    return Objects::toString($object);
}
