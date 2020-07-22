<?php

namespace Handlebars\compilers;

use FS;
use Closure;
use Handlebars\compilers\exception\CannotWrite;

class PluginCompiler
{
	protected $config = [];
	protected $plugins = [];

	public function __construct(array $config)
	{
		$defaultConfig = [
			'force compile' => true, /* boolean - always compile in developer mode */
			'cache folder' => '/var/views', /* string - folder inside cache folder if any */
			'cache prefix' => 'hbs.', /* string - prefix all HBCache cached entries with */
			'plugins' => [], /* must come in ['name'=>'path'] */
		];

		$this->config = \array_replace($defaultConfig, $config);

		/* we must have a working directory which is read and write */
		$this->makeCacheFolder($this->config['cache folder']);

		if (\is_array($this->config['plugins'])) {
			$this->compilePlugins($this->config['plugins']);
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

	protected function compilePlugins(array $pluginFiles)
	{
		$compiledPlugins = trim($this->config['cache folder'], '/') . '/' . $this->config['cache prefix'] . 'plugins.php';

		if (!$this->fileExists($compiledPlugins)) {
			$this->generateFile($pluginFiles, $compiledPlugins);
		}

		$this->plugins = $this->includePlugins($compiledPlugins);
	}

	protected function generateFile(array $pluginFiles, string $phpFile): bool
	{
		$bytesWritten = FS::file_put_contents($phpFile, $this->source($pluginFiles, true));

		FS::chmod($phpFile, 0666);

		return ($bytesWritten > 0);
	}

	protected function source(array $pluginFiles): string
	{
		$combined  = '<?php' . PHP_EOL . '/*' . PHP_EOL . 'DO NOT MODIFY THIS FILE' . PHP_EOL . 'Written: ' . date('Y-m-d H:i:s T') . PHP_EOL . '*/' . PHP_EOL . PHP_EOL;

		foreach ($pluginFiles as $file) {
			$pluginSource  = php_strip_whitespace(FS::resolve($file, false));
			$pluginSource  = trim(str_replace(['<?php', '<?', '?>'], '', $pluginSource));
			$pluginSource  = trim('/* ' . $file . ' */' . PHP_EOL . $pluginSource) . PHP_EOL . PHP_EOL;

			$combined .= $pluginSource;
		}

		return $combined;
	}

	protected function includePlugins(string $filePath): array
	{
		/* protected scoope */
		$helpers = [];

		require FS::resolve($filePath);

		return $helpers;
	}

	protected function makeCacheFolder(string $folder): void
	{
		/* let's make sure the compile folder is there before we try to save the compiled file! */
		if (!FS::file_exists($folder)) {
			FS::mkdir($folder, 0755, true);
		}

		/* is the folder writable by us? */
		if (!FS::is_writable($folder)) {
			throw new CannotWrite($folder);
		}
	}

	protected function fileExists(string $filePath): bool
	{
		return !($this->config['force compile'] || !FS::file_exists($filePath));
	}
} /* end class */
