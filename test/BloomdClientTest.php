<?php

require_once __DIR__ . "/../src/PhpBloomd/BloomdClient.php";
use PhpBloomd\BloomdClient as Client;

class BloomdClientTest extends PHPUnit_Framework_TestCase
{
	// Name of filter
	private static $filter = "phpunit";

	// Verify that it is possible to create a filter on bloomd server
	public function testCreateFilter()
	{
		$bloomd = new Client();

		// Drop any pre-existing filters to clear state
		$bloomd->dropFilter(self::$filter);
		$this->assertTrue($bloomd->createFilter(self::$filter));
	}

	// Verify that it is possible to set items into a filter on bloomd server, but not duplicates
	public function testSet()
	{
		$bloomd = new Client();

		// Set unique items, should return true
		$this->assertTrue($bloomd->set(self::$filter, "foo"));
		$this->assertTrue($bloomd->set(self::$filter, "bar"));

		// Should return false due to duplicate item
		$this->assertFalse($bloomd->set(self::$filter, "foo"));
	}

	// Verify that it is possible to check for items from a filter on bloomd server
	// NOTE: due to nature of bloom filters, it is possible to return true on an item which doesn't exist,
	// but NOT possible to return false on an item which does exist
	public function testCheck()
	{
		$bloomd = new Client();

		// Items which exist will always return true
		$this->assertTrue($bloomd->check(self::$filter, "foo"));
		$this->assertTrue($bloomd->check(self::$filter, "bar"));
	}

	// Verify that it is possible to bulk set values on bloomd server
	public function testBulk()
	{
		$bloomd = new Client();

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
		$bloomd = new Client();

		// Will return associative array of keys and their status
		// ex: array("foo" => true, "bar" => false), etc
		$results = $bloomd->multi(self::$filter, array("baz", "qux", "corge"));
		foreach ($results as $k => $v)
		{
			// Verify all true
			$this->assertTrue($v);
		}
	}

	// Verify that it is possible to check for any value in array on bloomd server
	public function testAny()
	{
		$bloomd = new Client();

		$this->assertTrue($bloomd->any(self::$filter, array("foo", "bar", "meow")));
	}

	// Verify that it is possible to check for all values in array on bloomd server
	public function testAll()
	{
		$bloomd = new Client();

		$this->assertTrue($bloomd->all(self::$filter, array("foo", "bar")));
	}

	// Verify that it is possible to list information about a filter by its name
	public function testListFilters()
	{
		$bloomd = new Client();

		$filter = $bloomd->listFilters(self::$filter);

		// Verify all fields contained in first filter
		$fields = array("name", "probability", "size", "capacity", "items");
		foreach ($fields as $f)
		{
			$this->assertArrayHasKey($f, $filter[0]);
		}
	}

	// Verify that it is possible to query extended information about a filter by its name
	public function testInfo()
	{
		$bloomd = new Client();

		$filter = $bloomd->info(self::$filter);

		// Verify all fields contained in filter information
		$fields = array(
			"capacity",
			"checks",
			"check_hits",
			"check_misses",
			"in_memory",
			"page_ins",
			"page_outs",
			"probability",
			"sets",
			"set_hits",
			"set_misses",
			"size",
			"storage",
		);

		foreach ($fields as $f)
		{
			$this->assertArrayHasKey($f, $filter);
		}
	}

	// Verify that it is possible to drop a filter on bloomd server
	public function testDropFilter()
	{
		$bloomd = new Client();

		$this->assertTrue($bloomd->dropFilter(self::$filter));
	}
}
