<?php

require_once 'vendor/autoload.php';

use MVCStarterPlugin\Lib\Router;
use MVCStarterPlugin\Application;

class RouterTest extends PHPUnit_Framework_TestCase
{
    public function testHasDefaultRoute()
    {
        $app = $this->getMockBuilder('MVCStarterPlugin\Application')
					->disableOriginalConstructor()
					->getMock();
		$app->expects($this->any())
			->method('getName')
         	->will($this->returnValue('test_application'));

        $wp = $this->getMockBuilder('MVCStarterPlugin\Lib\WPWrapper')
                   ->setMethods(array('get_query_var', 'get_option'))
                   ->getMock();
        
        $query_vars = array(
            array('test_application', ''),
            array('ctrl', ''),
            array('cmd', ''),
        );

        $wp->expects($this->any())
           ->method('get_query_var')
           ->will($this->returnValueMap($query_vars));

        $wp->expects($this->any())
           ->method('get_option')
           ->will($this->returnValue(array(
                'name' => "Test Application",
                'default_controller' => 'entities',
                'default_command' => 'show'
            )));

        $router = new Router($app, $wp);
        $cmd = $router->getCommand();
        $this->assertEquals('Entities', $cmd->getController());
        $this->assertEquals('show', $cmd->getAction());
    }

    public function testCreatesCommandIfQueryCommandGiven()
    {   
        $app = $this->getMockBuilder('MVCStarterPlugin\Application')
                    ->disableOriginalConstructor()
                    ->getMock();
        $app->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('test_application'));

        $wp = $this->getMockBuilder('MVCStarterPlugin\Lib\WPWrapper')
                   ->setMethods(array('get_query_var', 'get_option'))
                   ->getMock();
        
        $query_vars = array(
            array('test_application', 'true'),
            array('ctrl', 'another_controller'),
            array('cmd', 'the_action'),
        );

        $wp->expects($this->any())
           ->method('get_query_var')
           ->will($this->returnValueMap($query_vars));

        $wp->expects($this->any())
           ->method('get_option')
           ->will($this->returnValue(array(
                'name' => "Test Application",
                'default_controller' => 'entities',
                'default_command' => 'show'
            )));

        $router = new Router($app, $wp);
        $cmd = $router->getCommand();
        $this->assertEquals('AnotherController', $cmd->getController());
        $this->assertEquals('theAction', $cmd->getAction());
    }
}