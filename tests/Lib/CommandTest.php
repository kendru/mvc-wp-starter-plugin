<?php

require_once 'vendor/autoload.php';

use MVCStarterPlugin\Lib\Command;

class CommandTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyCommandCreatesNoOutput()
    {
        $cmd = new Command();
        ob_start();
        $cmd->execute();
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEmpty($output);
    }

    public function testGeneratesSaneControllerName()
    {
        $cmd = new Command('my_test_controller', 'call_this');
        $this->assertEquals('MyTestController', $cmd->getController());
    }

    public function testGeneratesSaneActionName()
    {
        $cmd = new Command('my_test_controller', 'call_this');
        $this->assertEquals('callThis', $cmd->getAction());
    }
}