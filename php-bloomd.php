<?php

class BloomdClient
{
	// CONSTANTS - - - - - - - - - - - - - - - - - - - - -

	// Constants for TCP or UDP connection to bloomd
	const BLOOMD_TCP = 0;
	const BLOOMD_UDP = 1;

	// Constants for string responses from bloomd
	const BLOOMD_DONE = "Done";
	const BLOOMD_EXISTS = "Exists";
	const BLOOMD_LIST_START = "START";
	const BLOOMD_LIST_END = "END";
	const BLOOMD_YES = "Yes";
	const BLOOMD_NO = "No";

	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - -

	// Server host, port, protocol
	protected $host;
	protected $port;
	protected $protocol;

	// The socket used for communication with bloomd
	protected $socket;

	// Connection status
	protected $connected = false;

	// CONSTRUCTOR/DESTRUCTOR - - - - - - - - - - - - - - -

	public function __construct($host, $port = 8673, $protocol = self::BLOOMD_TCP)
	{
		$this->host = $host;
		$this->port = $port;
		$this->protocol = $protocol;

		// If protocol is TCP, open persistent connection to server
		if ($protocol === self::BLOOMD_TCP)
		{
			$this->connect();
		}
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
			socket_connect($this->socket, $this->host, intval($this->port));

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
	public function createFilter($name, $capacity = null, $falsePositiveRate = null, $inMemory = null)
	{
		// Begin building command to send to server
		$buffer = "create " . $name . " ";

		// If specified, send capacity
		if (isset($capacity) && is_int($capacity))
		{
			$buffer .= "capacity=" . $capacity . " ";
		}

		// If specified, send false positive rate
		if (isset($falsePositiveRate) && is_float($falsePositiveRate))
		{
			$buffer .= "prob=" . $falsePositiveRate . " ";
		}

		// If specified, choose if filter should reside in memory
		if (isset($inMemory) && is_bool($inMemory))
		{
			// Bool to integer
			$inMemory = $inMemory ? 1 : 0;

			$buffer .= "in_memory=" . $inMemory;
		}

		$buffer .= "\n";

		// Send create filter request to server, verify done
		if ($this->send($buffer) === self::BLOOMD_DONE)
		{
			return true;
		}

		return false;
	}

	// Send a message to server on socket
	private function send($input)
	{
		if (!$this->connected || empty($this->socket))
		{
			throw new Exception(__METHOD__ . ": client is not connected to bloomd server!");
		}

		// Write message on socket, read reply
		printf("send: " . $input);
		socket_write($this->socket, $input);
		$response = trim(socket_read($this->socket, 8192), "\r\n");

		printf("recv: '" . $response . "'");
		return $response;
	}
}
