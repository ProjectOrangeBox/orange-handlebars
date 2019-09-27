<?php

#namespace \;

class HBCache {
	static $prefix = 'hbs.';
	static $adapter = 'dummy';
	static $setup = false;

	static public function setPrefix(string $prefix): void
	{
		self::$prefix = $prefix;
	}

	static public function setCacheType(string $adapter): void
	{
		if (!ci('cache')->is_supported($adapter)) {
			throw new Exception(sprintf('%s is a unsupported Cache Driver.',$adapter));
		}

		self::$adapter = $adapter;
	}

	/*
	This is used by the plugs to cache content

	cache="60"

	{{q:cache_demo cache="5"}}

	time is in minutes
	*/
	static public function set(array $options, $data): void
	{
		self::setup();

		ci('cache')->{self::$adapter}->save(self::makeKey($options), $data, (int)$options['hash']['cache']);
	}

	static public function get(array $options) /* mixed */
	{
		self::setup();

		return ci('cache')->{self::$adapter}->get(self::makeKey($options));
	}

	static protected function setup()
	{
		if (!self::$setup) {
			self::$adapter = ci('cache')->defaultAdapter();

			self::$setup = true;
		}
	}

	static protected function makeKey(array $options): string
	{
		return self::$prefix.md5(json_encode($options));
	}

} /* end class */