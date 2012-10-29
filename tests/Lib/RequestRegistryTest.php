<?php

require_once 'vendor/autoload.php';

use MVCStarterPlugin\Lib\RequestRegistry;

class RequestRegistryTest extends PHPUnit_Framework_TestCase
{
	protected static $reg;

	public static function setUpBeforeClass()
	{
		self::$reg = RequestRegistry::instance();
	}
    public function testCreatesRegistry()
    {
        $this->assertInstanceOf('MVCStarterPlugin\Lib\RequestRegistry', self::$reg, "RequestRegistry not created");
    }

    public function testSetsConflictMode()
    {
    	self::$reg->setConflictMode(RequestRegistry::CONFLICT_EXCEPTION);
    	$this->assertEquals(RequestRegistry::CONFLICT_EXCEPTION, self::$reg->getConflictMode(), 'Conflict Mode could not be set');
    }

    public function testSetsValue()
    {
    	try {
    		self::$reg->set('foo', 'bar');
    	} catch(\Exception $e) {
    		$this->fail("Setting registry value failed with message: " . $e->getMessage());
    	}
    	return true;
    }

    public function testGetsValue()
    {
    	$val = '';
    	try {
    		$val = self::$reg->get('foo');
    	} catch(\Exception $e) {
    		$this->fail("Setting registry value failed with message: " . $e->getMessage());
    	}
    	$this->assertEquals('bar', $val, "Incorrect value fetched from registry");
    }

    public function testExceptionConflictMode()
    {
    	self::$reg->setConflictMode(RequestRegistry::CONFLICT_EXCEPTION);
    	try {
    		self::$reg->set('foo', 'baz');
    	} catch (\Exception $e) {
    		return true;
    	}
    	$this->fail("Attempted overwrite did not raise exception");
    }

    public function testOverwriteConflictMode()
    {
    	self::$reg->setConflictMode(RequestRegistry::CONFLICT_OVERWRITE);
    	self::$reg->set('foo', 'baz');
    	$val = self::$reg->get('foo');
    	$this->assertEquals('baz', $val, "Registry did not overwrite previously set value");
    }

    public function testSilentConflictMode()
    {
    	self::$reg->setConflictMode(RequestRegistry::CONFLICT_SILENT);
    	self::$reg->set('foo', 'qux');
    	$val = self::$reg->get('foo');
    	$this->assertThat(
    		$val,
    		$this->logicalNot(
    			$this->equalTo('qux')
    		)
    	);
    }
}