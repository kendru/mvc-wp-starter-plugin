<?php

require_once 'vendor/autoload.php';

use MVCStarterPlugin\Lib\Router;
use MVCStarterPlugin\Application;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testDoesNothingByDefault()
    {
        $app = $this->getMockBuilder('MVCStarterPlugin\Application')
					->disableOriginalConstructor()
					->getMock();
		$app->expects($this->any())
			->method('getName')
         	->will($this->returnValue('test_application'));
        $router = new Router($app);
        $this->assertEquals(false, $router->canResolve());
    }

    public function testCreatesCommandIfQueryCommandGiven()
    {        
        $_REQUEST['test_application_ctl'] = "controller";
        $_REQUEST['test_application_cmd'] = "action";

		$app = $this->getMockBuilder('MVCStarterPlugin\Application')
					->disableOriginalConstructor()
					->getMock();
		$app->expects($this->any())
			->method('getName')
         	->will($this->returnValue('test_application'));

        $router = new Router($app);
        $this->assertEquals(true, $router->canResolve());
        $this->assertInstanceOf('MVCStarterPlugin\Lib\Command', $router->getCommand());

        $_REQUEST['test_application_ctl'] = null;
        $_REQUEST['test_application_cmd'] = null;
    }
}