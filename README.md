php-bloomd
==========

PHP 5.4+ class for interacting with a bloomd server (https://github.com/armon/bloomd).  MIT Licensed.

Installation
------------

php-bloomd can be installed via Composer.  Add `"mdlayher/php-bloomd": "dev-master"` to the `require` section
of your `composer.json` and run `composer install`.

Testing
-------

php-bloomd can be tested using PHPUnit.  Simply run `phpunit test` from the project root with a local bloomd
server running on port 8673.

Example
-------

All commands accepted by bloomd are implemented in php-bloomd.  Here is a basic example script.

```php
<?php
// php-bloomd - Example basic usage script
require_once __DIR__ . "/vendor/autoload.php";

// Establish a connection to a local bloomd with client
$bloomd = new PhpBloomd\BloomdClient("localhost", 8673);
if (!$bloomd->connect())
{
	printf("example: failed to connect\n");
	exit;
}

// Create a filter
if (!$bloomd->createFilter("php"))
{
	printf("example: failed to create filter\n");
	exit;
}

// Set a couple of values in filter
$bloomd->set("php", "foo");
$bloomd->set("php", "bar");

// Check the filter for membership
if ($bloomd->check("php", "foo"))
{
	printf("example: got it!\n");
}

// Bulk set values
$results = $bloomd->bulk("php", array("foo", "bar", "baz"));
foreach ($results as $k => $v)
{
	printf("%s -> %s\n", $k, $v ? "true" : "false");
}

// Multi check values
$results = $bloomd->multi("php", array("foo", "bar", "baz"));
foreach ($results as $k => $v)
{
	printf("%s -> %s\n", $k, $v ? "true" : "false");
}

// Drop filter, disconnect
$bloomd->dropFilter("php");
$bloomd->disconnect();
```
