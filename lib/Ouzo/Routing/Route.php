<?php
namespace Ouzo\Routing;

use InvalidArgumentException;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\Strings;

/**
 * Route define URLs mapping to controller and actions.
 *
 * Sample usage:
 * <code>
 *  Route::get('/agents/index', 'agents#index'); will match: GET /agents/index
 *  Route::post('/agents/save', 'agents#save'); will match: POST /agents/save
 *  Route::resource('agents'); will mapping RESTs methods (index, fresh, edit, show, create, update, destroy)
 *  Route::any('/agents/show_numbers', 'agents#show_numbers'); will match: POST or GET /agents/show_numbers
 *  Route::allowAll('/agents', 'agents'); will mapping any methods to all actions in controller
 * </code>
 *
 * To show all routes or routes per controller:
 * <code>
 *  Route::getRoutes();
 *  Route::getRoutesForController('agents');
 * </code>
 */
class Route
{
    /**
     * @var RouteRule[]
     */
    public static $routes = array();
    public static $methods = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE');
    public static $validate = true;


    public static function get($uri, $action, array $options = array())
    {
        self::_addRoute('GET', $uri, $action, true, $options);
    }

    public static function post($uri, $action, array $options = array())
    {
        self::_addRoute('POST', $uri, $action, true, $options);
    }

    public static function any($uri, $action, array $options = array())
    {
        self::_addRoute(self::$methods, $uri, $action, true, $options);
    }

    public static function resource($controller)
    {
        self::_addRoute('GET',
            self::_createRouteUri($controller),
            self::_createRouteAction($controller, 'index'),
            true, array(), true
        );
        self::_addRoute('GET',
            self::_createRouteUri($controller, '/fresh'),
            self::_createRouteAction($controller, 'fresh'),
            true, array(), true
        );
        self::_addRoute('GET',
            self::_createRouteUri($controller, '/:id/edit'),
            self::_createRouteAction($controller, 'edit'),
            true, array(), true
        );
        self::_addRoute('GET',
            self::_createRouteUri($controller, '/:id'),
            self::_createRouteAction($controller, 'show'),
            true, array(), true
        );
        self::_addRoute('POST',
            self::_createRouteUri($controller),
            self::_createRouteAction($controller, 'create'),
            true, array(), true
        );
        self::_addRoute('PUT',
            self::_createRouteUri($controller, '/:id'),
            self::_createRouteAction($controller, 'update'),
            true, array(), true
        );
        self::_addRoute('PATCH',
            self::_createRouteUri($controller, '/:id'),
            self::_createRouteAction($controller, 'update'),
            true, array(), true
        );
        self::_addRoute('DELETE',
            self::_createRouteUri($controller, '/:id'),
            self::_createRouteAction($controller, 'destroy'),
            true, array(), true
        );
    }

    public static function allowAll($uri, $controller, $options = array())
    {
        self::_addRoute(self::$methods, $uri, $controller, false, $options);
    }

    private static function _createRouteUri($action, $suffix = '')
    {
        return '/' . $action . $suffix;
    }

    private static function _createRouteAction($controller, $action)
    {
        return $controller . '#' . $action;
    }

    private static function _addRoute($method, $uri, $action, $requireAction = true, $options = array(), $isResource = false)
    {
        if (self::$validate && self::_existRouteRule($method, $uri)) {
            $methods = is_array($method) ? implode(', ', $method) : $method;
            throw new InvalidArgumentException('Route rule for method ' . $methods . ' and URI "' . $uri . '" already exists');
        }

        $routeRule = new RouteRule($method, $uri, $action, $requireAction, $options, $isResource);
        if ($routeRule->hasRequiredAction()) {
            throw new InvalidArgumentException('Route rule ' . $uri . ' required action');
        }
        self::$routes[] = $routeRule;
    }

    private static function _existRouteRule($method, $uri)
    {
        return Arrays::any(self::getRoutes(), function (RouteRule $routeRule) use ($method, $uri) {
            return $routeRule->getMethod() == $method && $routeRule->getUri() == $uri;
        });
    }

    /**
     * @return RouteRule[]
     */
    public static function getRoutes()
    {
        return self::$routes;
    }

    public static function getRoutesForController($controller)
    {
        return Arrays::filter(self::getRoutes(), function (RouteRule $route) use ($controller) {
            return Strings::equalsIgnoreCase($route->getController(), $controller);
        });
    }
}