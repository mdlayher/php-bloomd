<?php

namespace PhpBloomd;

interface IBloomdClient
{
	public function filter($name);

	public function createFilter($name, $capacity = null, $probability = null, $inMemory = null);
	public function closeFilter($name);
	public function clearFilter($name);
	public function dropFilter($name);
	public function flushFilter($name);
	public function listFilters($name = null);
	public function info($name);

	public function check($filter, $value);
	public function set($filter, $value);

	public function bulk($filter, array $items);
	public function multi($filter, array $items);
	public function any($filter, array $items);
	public function all($filter, array $items);
}
