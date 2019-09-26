<?php

#namespace \;

class HBCache {
	static $prefix = 'hbs.';

	static public function setPrefix(string $prefix): void
	{
		self::$prefix = $prefix;
	}

	/*
	This is used by the plugs to cache content

	cache="60"

	{{q:cache_demo cache="5"}}

	time is in minutes
	*/
	static public function set(array $options, $data)
	{
		return (self::test($options)) ? ci('cache')->save(self::makeKey($options), $data) : false;
	}

	static public function get(array $options)
	{
		return (self::test($options)) ? ci('cache')->get(self::makeKey($options)) : false;
	}

	static protected function test(array $options): bool
	{
		return ((int) $options['hash']['cache'] > 0 && (env('DEBUG') != 'development'));
	}

	static protected function makeKey(array $options): string
	{
		return self::$prefix.md5(json_encode($options));
	}

} /* end class */