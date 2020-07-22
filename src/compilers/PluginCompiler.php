<?php

namespace projectorangebox\handlebars\compilers;

use FS;
use Closure;
use projectorangebox\handlebars\compilers\Cache;

class PluginCompiler
{
	protected $config = [];
	protected $plugins = [];

	protected $cache = null;

	public function __construct(array $config)
	{
		$this->config = $config;

		$this->cache = new Cache($this->config);

		if (isset($this->config['plugins']) && \is_array($this->config['plugins'])) {
			$this->plugins = $this->compilePlugins($this->config['plugins']);
		}
	}

	public function getPlugins(): array
	{
		return $this->plugins;
	}

	public function addPlugin(string $name, Closure $closure): void
	{
		$this->plugins[strtolower($name)] = $closure;
	}

	public function addPlugins(array $plugins): void
	{
		foreach ($plugins as $name => $closure) {
			$this->addPlugin($name, $closure);
		}
	}

	protected function compilePlugins(array $pluginFiles): array
	{
		$key = 'plugins';

		if (!$this->cache->get($key)) {
			$source = $this->source($pluginFiles);

			$this->cache->save($key, $source);
		}

		return $this->cache->include($key);
	}

	protected function source(array $pluginFiles): string
	{
		$combined  = '<?php' . PHP_EOL;
		$combined .= '/* DO NOT MODIFY THIS FILE - Written: ' . date('Y-m-d H:i:s T') . '*/' . PHP_EOL;
		$combined .= PHP_EOL;

		foreach ($pluginFiles as $file) {
			$pluginSource  = php_strip_whitespace(FS::resolve($file, false));
			$pluginSource  = trim(str_replace(['<?php', '<?', '?>'], '', $pluginSource));
			$pluginSource  = trim('/* ' . $file . ' */' . PHP_EOL . $pluginSource) . PHP_EOL . PHP_EOL;

			$combined .= $pluginSource;
		}

		$combined .= 'return $helpers;' . PHP_EOL;

		return $combined;
	}
} /* end class */
