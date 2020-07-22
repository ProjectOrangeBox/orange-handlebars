<?php

namespace Handlebars\compilers;

use FS;
use Exception;
use LightnCandy\LightnCandy;

class ViewCompiler
{
	protected $config = [];
	protected $handlebars = null;
	protected $plugins = null;

	public function __construct(array $config = [], $plugins, $handlebars)
	{
		$defaultConfig = [
			'force compile' => true, /* boolean - always compile in developer mode */
			'cache folder' => '/var/views', /* string - folder inside cache folder if any */
			'cache prefix' => 'hbs.', /* string - prefix all HBCache cached entries with */
			'delimiters' => ['{{', '}}'], /* array */
			/* lightncandy handlebars compiler flags https://github.com/zordius/lightncandy#compile-options */
			'flags' => LightnCandy::FLAG_ERROR_EXCEPTION | LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_RUNTIMEPARTIAL, /* integer */
			'plugins' => [], /* must come in ['name'=>'path'] */
		];

		$this->config = \array_replace($defaultConfig, $config);

		$this->plugins = $plugins;
		$this->handlebars = $handlebars;

		/* we must have a working directory which is read and write */
		$this->makeCacheFolder($this->config['cache folder']);
	}

	public function getView(string $viewPath)
	{
		/* build the compiled file path */
		$compiledFile = $this->config['cache folder'] . '/' . $this->config['cache prefix'] . md5($viewPath) . '.php';

		if (!$this->fileExists($compiledFile)) {
			FS::file_put_contents($compiledFile, $this->compile($this->fileGetContents($viewPath), '/* ' . $viewPath . ' compiled @ ' . date('Y-m-d h:i:s e') . ' */'));
		}

		$templatePHP = FS::require($compiledFile);

		/* is what we loaded even executable? */
		if (!is_callable($templatePHP)) {
			throw new Exception('Could not execute template');
		}

		return $templatePHP;
	}

	/*
	* set the template delimiters
	*
	* @param string/array
	* @param string
	* @return object (this)
	*/
	public function setDelimiters(/* string|array */$l = '{{', string $r = '}}'): void
	{
		/* set delimiters */
		$this->config['delimiters'] = (is_array($l)) ? $l : [$l, $r];
	}

	/**
	 * heavy lifter - wrapper for lightncandy https://github.com/zordius/lightncandy handlebars compiler
	 *
	 * returns a executable php function
	 *
	 */
	protected function compile(string $templateSource, string $comment = ''): string
	{
		/* Compile it into php magic! Thank you zordius https://github.com/zordius/lightncandy */
		$source = LightnCandy::compile($templateSource, [
			'flags' => $this->config['flags'], /* compiler flags */
			'helpers' => $this->plugins->getPlugins(),
			'renderex' => $comment,
			'delimiters' => $this->config['delimiters'],
			'partialresolver' => function ($context, $name) { /* partial & template handling */
				try {
					/* raw template source not compiled */
					$template = FS::file_get_contents($this->handlebars->getView($name));
				} catch (Exception $e) {
					$template = '<!-- view named "' . $name . '" could not found --!>';
				}

				return $template;
			},
		]);

		return '<?php' . PHP_EOL . $source . PHP_EOL . '?>';
	}

	protected function fileExists(string $filePath): bool
	{
		/* always compile in development or not save or compile if doesn't exist */
		return !($this->config['force compile'] || !FS::file_exists($filePath));
	}

	protected function makeCacheFolder(string $folder): void
	{
		/* let's make sure the compile folder is there before we try to save the compiled file! */
		if (!FS::file_exists($folder)) {
			FS::mkdir($folder, 0755, true);
		}

		/* is the folder writable by us? */
		if (!FS::is_writable($folder)) {
			throw new Exception('Cannot write to folder ' . $folder);
		}
	}

	protected function fileGetContents(string $file): string
	{
		if (!FS::file_exists($file)) {
			throw new Exception('Can not location the file "' . $file . '".');
		}

		return FS::file_get_contents($file);
	}
} /* end class */