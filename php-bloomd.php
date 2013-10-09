<?php

class BloomdClient
{
	// CONSTANTS - - - - - - - - - - - - - - - - - - - - -

	// Constants for string responses from bloomd
	const BLOOMD_DONE = "Done";
	const BLOOMD_EXISTS = "Exists";
	const BLOOMD_LIST_START = "START";
	const BLOOMD_LIST_END = "END";
	const BLOOMD_YES = "Yes";
	const BLOOMD_NO = "No";

	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - -

	// Server host, port
	protected $host;
	protected $port;

	// The socket used for communication with bloomd
	protected $socket;

	// Connection status
	protected $connected = false;

	// CONSTRUCTOR/DESTRUCTOR - - - - - - - - - - - - - - -

	public function __construct($host, $port = 8673)
	{
		$this->host = $host;
		$this->port = $port;
	}

	public function __destruct()
	{
		// Ensure open connections closed
		if ($this->connected || isset($this->socket))
		{
			$this->disconnect();
		}

		return true;
	}

	// Initiate a connection to bloomd server
	public function connect()
	{
		if (!$this->connected)
		{
			// Create a IPv4 TCP socket
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));

			// Connect to host
			if (!@socket_connect($this->socket, $this->host, intval($this->port)))
			{
				return false;
			}

			$this->connected = true;
			return true;
		}

		return false;
	}

	// Close a connection to bloomd server
	public function disconnect()
	{
		// Verify socket is actually open
		if ($this->connected && isset($this->socket))
		{
			// Close socket
			socket_close($this->socket);

			$this->connected = false;
			return true;
		}

		return false;
	}

	// Create a bloom filter on server
	public function createFilter($name, $capacity = null, $probability = null, $inMemory = null)
	{
		// Begin building command to send to server
		$buffer = "create " . $name . " ";

		// If specified, send capacity
		if (isset($capacity) && is_int($capacity))
		{
			$buffer .= "capacity=" . $capacity . " ";
		}

		// If specified, send false positive rate
		if (isset($probability) && is_float($probability))
		{
			$buffer .= "prob=" . $probability . " ";
		}

		// If specified, choose if filter should reside in memory
		if (isset($inMemory) && is_bool($inMemory))
		{
			// Bool to integer
			$inMemory = $inMemory ? 1 : 0;

			$buffer .= "in_memory=" . $inMemory;
		}

		// Send create filter request to server, verify done
		if ($this->send($buffer) === self::BLOOMD_DONE)
		{
			return true;
		}

		return false;
	}

	// Set multiple items in filter on server
	public function bulk($filter, array $items)
	{
		// Build command, add all items
		$buffer = "bulk " . $filter . " ";
		foreach ($items as $i)
		{
			$buffer .= $i . " ";
		}

		// Set items, record status
		$response = explode(" ", $this->send($buffer));

		// Verify response received
		if (empty($response[0]))
		{
			return null;
		}

		// Create associative array of keys and booleans of whether or not they were successfully set
		$status = array();
		for ($i = 0; $i < count($items); $i++)
		{
			$status[$items[$i]] = $response[$i] === self::BLOOMD_YES;
		}

		return $status;
	}

	// Check for multiple items in filter on server
	public function multi($filter, array $items)
	{
		// Build command, add all items
		$buffer = "multi " . $filter . " ";
		foreach ($items as $i)
		{
			$buffer .= $i . " ";
		}

		// Set items, record status
		$response = explode(" ", $this->send($buffer));

		// Create associative array of keys and booleans of whether or not they were successfully set
		$status = array();
		for ($i = 0; $i < count($items); $i++)
		{
			$status[$items[$i]] = $response[$i] === self::BLOOMD_YES;
		}

		return $status;
	}

	// Close an in-memory filter on server
	public function closeFilter($name)
	{
		return $this->send("close " . $name) === self::BLOOMD_DONE;
	}

	// Clear an in-memory filter on server
	// NOTE: Should only be called after filter is closed
	public function clearFilter($name)
	{
		return $this->send("clear " . $name) === self::BLOOMD_DONE;
	}

	// Drop a bloom filter on server
	public function dropFilter($name)
	{
		return $this->send("drop " . $name) === self::BLOOMD_DONE;
	}

	// Flush data from a specified filter
	public function flushFilter($name)
	{
		return $this->send("flush " . $name) === self::BLOOMD_DONE;
	}

	// Retrieve a list of filters and their status by matching name, or all filters if none provided
	public function listFilters($name = null)
	{
		// Send list request
		$response = $this->send("list " . $name);

		// List of statuses to send back
		$list = array();

		// Parse through multi line response
		foreach (explode("\n", $response) as $line)
		{
			// Strip newlines, ignore start and end messages
			$line = trim($line, "\r\n");
			if ($line === self::BLOOMD_LIST_START || $line === self::BLOOMD_LIST_END)
			{
				continue;
			}

			// Convert status into associative arrays
			$fields = array("name", "probability", "size", "capacity", "items");
			$list[] = array_combine($fields, explode(" ", $line));
		}

		return $list;
	}

	// Retrieve detailed information about filter with specified name
	public function info($name)
	{
		// Send info request
		$response = $this->send("info " . $name);

		// Status associative array
		$status = array();

		// Parse through multi line response
		foreach (explode("\n", $response) as $line)
		{
			// Check for bad response
			if ($line == "Filter does not exist")
			{
				break;
			}

			// Strip newlines, ignore start and end messages
			$line = trim($line, "\r\n");
			if ($line === self::BLOOMD_LIST_START || $line === self::BLOOMD_LIST_END)
			{
				continue;
			}

			// Split into keys and values
			$pair = explode(" ", $line);
			$status[$pair[0]] = $pair[1];
		}

		return $status;
	}

	// Check if value is in filter
	public function check($filter, $value)
	{
		return $this->send(sprintf("check %s %s", $filter, $value)) === self::BLOOMD_YES;
	}

	// Set a value in a specified filter
	public function set($filter, $value)
	{
		return $this->send(sprintf("set %s %s", $filter, $value)) === self::BLOOMD_YES;
	}

	// Send a message to server on socket
	private function send($input)
	{
		if (!$this->connected || empty($this->socket))
		{
			throw new Exception(__METHOD__ . ": client is not connected to bloomd server!");
		}

		// Write message on socket, read reply
		printf("send: " . $input . "\n");
		@socket_write($this->socket, $input . "\n");
		$response = trim(@socket_read($this->socket, 8192), "\r\n");

		// Throw exception if no response
		if (empty($response))
		{
			throw new Exception(__METHOD__ . ": received empty response from bloomd server!");
		}

		printf("recv: '" . $response . "'\n");
		return $response;
	}
}
