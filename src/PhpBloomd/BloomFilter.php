<?php

namespace PhpBloomd;

class BloomFilter
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - -

	// Name of filter
	protected $name;

	// Instance of bloomd client
	protected $client;

	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - -

	public function __construct($name, BloomdClient $client)
	{
		$this->name = $name;
		$this->client = $client;
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - -

	// Call any functions using a named filter IBloomdClient, inserting this filter's name
	public function __call($name, $args)
	{
		// Disallowed methods for this object
		$disallowed = array("connect", "disconnect");

		if (method_exists($this->client, $name) && !in_array($name, $disallowed))
		{
			// Add filter name to arguments
			array_unshift($args, $this->name);

			return call_user_func_array(array($this->client, $name), $args);
		}

		throw new \Exception("BloomFilter->" . $name . ": not implemented or not allowed");
	}
}
