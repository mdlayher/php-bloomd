<?php

require_once __DIR__ . "/../src/PhpBloomd/BloomdClient.php";
use PhpBloomd\BloomdClient as Client;

class BloomdClientTest extends PHPUnit_Framework_TestCase
{
	// Name of filter
	private static $filter = "phpunit";

	// Verify that it is possible to connect and disconnect to a local bloomd server
	public function testConnect()
	{
		$bloomd = new Client("localhost");

		$this->assertTrue($bloomd->connect());
		$this->assertTrue($bloomd->disconnect());
	}

	// Verify that it is possible to create a filter on bloomd server
	public function testCreateFilter()
	{
		$bloomd = new Client("localhost");
		$bloomd->connect();

		// Drop any pre-existing filters to clear state
		$bloomd->dropFilter(self::$filter);
		$this->assertTrue($bloomd->createFilter(self::$filter));

		$bloomd->disconnect();
	}

	// Verify that it is possible to set items into a filter on bloomd server, but not duplicates
	public function testSet()
	{
		$bloomd = new Client("localhost");
		$bloomd->connect();

		// Set unique items, should return true
		$this->assertTrue($bloomd->set(self::$filter, "foo"));
		$this->assertTrue($bloomd->set(self::$filter, "bar"));

		// Should return false due to duplicate item
		$this->assertFalse($bloomd->set(self::$filter, "foo"));

		$bloomd->disconnect();
	}

	// Verify that it is possible to check for items from a filter on bloomd server
	// NOTE: due to nature of bloom filters, it is possible to return true on an item which doesn't exist,
	// but NOT possible to return false on an item which does exist
	public function testCheck()
	{
		$bloomd = new Client("localhost");
		$bloomd->connect();

		// Items which exist will always return true
		$this->assertTrue($bloomd->check(self::$filter, "foo"));
		$this->assertTrue($bloomd->check(self::$filter, "bar"));

		$bloomd->disconnect();
	}

	// Verify that it is possible to bulk set values on bloomd server
	public function testBulk()
	{
		$bloomd = new Client("localhost");
		$bloomd->connect();

		// Will return associative array of keys and their status
		// ex: array("foo" => true, "bar" => false), etc
		$results = $bloomd->bulk(self::$filter, array("baz", "qux", "corge"));
		foreach ($results as $k => $v)
		{
			// Verify all true
			$this->assertTrue($v);
		}
	}

	// Verify that it is possible to multi check values on bloomd server
	public function testMulti()
	{
		$bloomd = new Client("localhost");
		$bloomd->connect();

		// Will return associative array of keys and their status
		// ex: array("foo" => true, "bar" => false), etc
		$results = $bloomd->multi(self::$filter, array("baz", "qux", "corge"));
		foreach ($results as $k => $v)
		{
			// Verify all true
			$this->assertTrue($v);
		}
	}

	// Verify that it is possible to drop a filter on bloomd server
	public function testDropFilter()
	{
		$bloomd = new Client("localhost");
		$bloomd->connect();

		$this->assertTrue($bloomd->dropFilter(self::$filter));

		$bloomd->disconnect();
	}
}
