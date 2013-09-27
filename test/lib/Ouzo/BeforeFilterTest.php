<?php
use Ouzo\Controller;
use Ouzo\Routing\Route;
use Ouzo\Tests\ControllerTestCase;
use Ouzo\Utilities\Arrays;

class SampleControllerException extends Exception
{
}

class SampleController extends Controller
{
    function __construct($routeRule)
    {
        parent::__construct($routeRule);
    }

    public function init()
    {
        $this->before[] = 'beforeAction';
    }

    public function beforeAction()
    {
        return false;
    }

    public function action()
    {
        throw new SampleControllerException("This action shouldn't be called!");
    }
}

class MockControllerResolver
{
    public function getController()
    {
        $routeRule = Arrays::first(Route::getRoutes());
        return new SampleController($routeRule);
    }
}

class BeforeFilterTest extends ControllerTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_frontController->controllerResolver = new MockControllerResolver();
        $this->_frontController->redirectHandler = $this->getMock('\Ouzo\RedirectHandler', array('redirect'));
        Route::$routes = array();
    }

    /**
     * @test
     */
    public function shouldNotInvokeActionWhenBeforeFilterReturnFalse()
    {
        //given
        Route::any('/sample/action', 'sample#action');

        //when
        try {
            $this->get('/sample/action');
        } catch (SampleControllerException $exception) {
            $this->fail();
        }
    }
}