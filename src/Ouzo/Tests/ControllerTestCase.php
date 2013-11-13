<?php
namespace Ouzo\Tests;

use Ouzo\Config;
use Ouzo\FrontController;
use Ouzo\Utilities\Arrays;
use Ouzo\Utilities\FluentArray;
use Ouzo\Utilities\Functions;
use Ouzo\Utilities\Strings;

class ControllerTestCase extends DbTransactionalTestCase
{
    protected $_frontController;
    private $_prefixSystem;
    private $_redirectHandler;
    private $_sessionInitializer;
    private $_downloadHandler;

    public function __construct()
    {
        $config = Config::getValue('global');
        $this->_prefixSystem = $config['prefix_system'];

        $this->_redirectHandler = new MockRedirectHandler();
        $this->_sessionInitializer = new MockSessionInitializer();
        $this->_downloadHandler = new MockDownloadHandler();

        $this->_frontController = new FrontController();
        $this->_frontController->redirectHandler = $this->_redirectHandler;
        $this->_frontController->sessionInitializer = $this->_sessionInitializer;
        $this->_frontController->downloadHandler = $this->_downloadHandler;
        $this->_frontController->outputDisplayer = new MockOutputDisplayer();
    }

    public function get($url)
    {
        $_SERVER['REQUEST_URI'] = $this->_prefixSystem . $url;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = $this->_parseUrlParams($_SERVER['REQUEST_URI']);

        $this->_initFrontController();
    }

    private function _parseUrlParams($url)
    {
        $urlComponents = parse_url($url);
        $urlQuery = isset($urlComponents['query']) ? $urlComponents['query'] : '';
        $args = explode('&', $urlQuery);
        return FluentArray::from($args)
            ->filter(Functions::notEmpty())
            ->map(function ($arg) { return explode('=', $arg); })
            ->toMap(function ($keyValue) { return $keyValue[0]; }, function ($keyValue) {
                return urldecode(Arrays::getValue($keyValue, 1));
            })->toArray();
    }

    private function _initFrontController()
    {
        $this->_frontController->init();
    }

    public function post($url, $data)
    {
        $_SERVER['REQUEST_URI'] = $this->_prefixSystem . $url;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = $data;
        $_GET = $this->_parseUrlParams($_SERVER['REQUEST_URI']);

        $this->_initFrontController();
    }

    public function put($url, $data)
    {
        $_SERVER['REQUEST_URI'] = $this->_prefixSystem . $url;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array_merge($data, array('_method' => 'PUT'));
        $_GET = $this->_parseUrlParams($_SERVER['REQUEST_URI']);
        $this->_initFrontController();
    }

    public function patch($url)
    {
        $_SERVER['REQUEST_URI'] = $this->_prefixSystem . $url;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'PATCH';
        $_GET = $this->_parseUrlParams($_SERVER['REQUEST_URI']);
        $this->_initFrontController();
    }

    public function delete($url)
    {
        $_SERVER['REQUEST_URI'] = $this->_prefixSystem . $url;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['_method'] = 'DELETE';
        $_GET = $this->_parseUrlParams($_SERVER['REQUEST_URI']);
        $this->_initFrontController();
    }

    public function assertRedirectsTo($string)
    {
        $this->assertEquals($this->_removePrefix($string), $this->_removePrefix($this->_redirectHandler->getLocation()));
    }

    private function _removePrefix($string)
    {
        return Strings::removePrefix($string, $this->_prefixSystem);
    }

    public function assertRenders($string)
    {
        $statusResponse = $this->_frontController->getCurrentControllerObject()->getStatusResponse();
        $location = $this->_frontController->getCurrentControllerObject()->getRedirectLocation();
        if ($statusResponse != 'show') {
            $this->fail("Expected render $string but was $statusResponse $location");
        }
        $this->assertEquals($string, $this->_frontController->getCurrentControllerObject()->view->getViewName());
    }

    public function assertAssignsModel($variable, $modelObject)
    {
        $modelVariable = $this->_frontController->getCurrentControllerObject()->view->$variable;
        $this->assertNotNull($modelVariable);
        $this->assertEquals($modelObject->attributes(), $modelVariable->attributes());
    }

    public function assertDownloadFile($file)
    {
        $this->assertEquals($file, $this->_downloadHandler->getFileName());
    }

    public function assertAssignsValue($variable, $value)
    {
        $this->assertNotNull($this->_frontController->getCurrentControllerObject()->view->$variable);
        $this->assertEquals($value, $this->_frontController->getCurrentControllerObject()->view->$variable);
    }

    public function assertRendersContent($content)
    {
        $this->assertEquals($content, $this->_frontController->getCurrentControllerObject()->layout->layoutContent());
    }

    public function assertRendersNotEqualContent($content)
    {
        $this->assertNotEquals($content, $this->_frontController->getCurrentControllerObject()->layout->layoutContent());
    }

    public function assertRenderedJsonAttributeEquals($attribute, $equals)
    {
        $json = $this->getRenderedJsonAsArray();
        $this->assertEquals($equals, $json[$attribute]);
    }

    public function getAssigned($name)
    {
        return $this->_frontController->getCurrentControllerObject()->view->$name;
    }

    public function assertAttributesEquals($expected, $actual)
    {
        $this->assertEquals($expected->attributes(), $actual->attributes());
    }

    public function getRenderedJsonAsArray()
    {
        return json_decode($this->_frontController->getCurrentControllerObject()->layout->layoutContent(), true);
    }
}