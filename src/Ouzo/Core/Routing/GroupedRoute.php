<?php
/*
 * Copyright (c) Ouzo contributors, http://ouzoframework.org
 * This file is made available under the MIT License (view the LICENSE file for more information).
 */
namespace Ouzo\Routing;

class GroupedRoute implements RouteInterface
{
    private static $name;

    public static function setGroupName($name)
    {
        self::$name = $name;
    }

    public static function get($uri, $action, array $options = [])
    {
        Route::get(self::uri($uri), self::action($action), $options);
    }

    public static function post($uri, $action, array $options = [])
    {
        Route::post(self::uri($uri), self::action($action), $options);
    }

    public static function put($uri, $action, array $options = [])
    {
        Route::put(self::uri($uri), self::action($action), $options);
    }

    public static function delete($uri, $action, array $options = [])
    {
        Route::delete(self::uri($uri), self::action($action), $options);
    }

    public static function any($uri, $action, array $options = [])
    {
        Route::any(self::uri($uri), self::action($action), $options);
    }

    public static function resource($controller)
    {
        Route::resource(self::uri($controller));
    }

    private static function uri($uri)
    {
        return '/' . self::$name . '/' . ltrim($uri, '/');
    }

    private static function action($action)
    {
        return self::$name . '/' . $action;
    }
}
