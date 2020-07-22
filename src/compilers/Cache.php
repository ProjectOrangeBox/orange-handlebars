<?php

namespace projectorangebox\handlebars\compilers;

use FS;
use projectorangebox\cache\CacheInterface;
use projectorangebox\handlebars\exceptions\CannotWrite;

class Cache implements CacheInterface
{
	protected $config = [];

	protected $cachePath = '';
	protected $cachePrefix = '';
	protected $forceCompile = true;

	public function __construct(array $config)
	{
		$this->config = $config;

		$this->forceCompile = $config['force compile'] ?? $this->forceCompile;

		$this->cachePrefix = $config['cache prefix'] ?? $this->cachePrefix;

		/* we must have a working directory which is read and write */
		$this->cachePath = $this->makeCacheFolder($this->config['cache folder']);
	}

	public function get(string $key)
	{
		\log_message('info', 'Handlebars Cache Get ' . $key);

		$cacheFile = $this->getCachePath($key);

		$value = false;

		if ($this->fileExists($cacheFile)) {
			$cacheFileAbs = FS::resolve($cacheFile);

			$value = include $cacheFileAbs;
		}

		return $value;
	}

	/* use only if you know it's there and want to ignore force compile setting */
	public function include(string $key)
	{
		\log_message('info', 'Handlebars Cache Include ' . $key);

		return include FS::resolve($this->getCachePath($key));
	}

	public function save(string $key, $value, int $ttl = null, bool $raw = false): bool
	{
		\log_message('info', 'Handlebars Cache Save ' . $key);

		$cacheFile = $this->getCachePath($key);

		$bytesWritten = FS::file_put_contents($cacheFile, $value);

		FS::chmod($cacheFile, 0666);

		return ($bytesWritten > 0);
	}

	public function delete(string $key)
	{
		\log_message('info', 'Handlebars Cache Delete ' . $key);

		$cacheFile = $this->getCachePath($key);

		if (FS::file_exists($cacheFile)) {
			FS::unlink($cacheFile);
		}
	}

	public function getMetadata(string $key): array
	{
		\log_message('info', 'Handlebars MetaData ' . $key);

		$cacheFile = $this->getCachePath($key);

		$metaData = [];

		if (FS::is_file($cacheFile)) {
			$metaData['name'] = basename($cacheFile);
			$metaData['created'] = FS::filemtime($cacheFile);
		}

		return $metaData;
	}

	public function cacheInfo(): array
	{
		\log_message('info', 'Handlebars Cache Info');

		$filenames = [];

		foreach (FS::glob($this->cachePath . '*') as $path) {
			$filenames[] = FS::basename($path);
		}

		return $filenames;
	}

	public function clean(): void
	{
		\log_message('info', 'Handlebars Cache Clean');

		foreach (FS::glob($this->cachePath . '*') as $path) {
			$this->delete($path);
		}
	}

	protected function getCachePath(string $key): string
	{
		return $this->cachePath . $this->cachePrefix . $key . '.php';
	}

	protected function makeCacheFolder(string $folder): string
	{
		$folder = trim($folder, '/');

		/* let's make sure the compile folder is there before we try to save the compiled file! */
		if (!FS::file_exists($folder)) {
			FS::mkdir($folder, 0755, true);
		}

		/* is the folder writable by us? */
		if (!FS::is_writable($folder)) {
			throw new CannotWrite($folder);
		}

		return $folder . '/';
	}

	protected function fileExists(string $filePath): bool
	{
		return !($this->forceCompile || !FS::file_exists($filePath));
	}
} /* end class */
