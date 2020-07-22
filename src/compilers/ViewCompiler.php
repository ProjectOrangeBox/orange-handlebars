<?php

namespace projectorangebox\handlebars\compilers;

use FS;
use Exception;
use LightnCandy\LightnCandy;
use projectorangebox\handlebars\compilers\Cache;
use projectorangebox\handlebars\exceptions\CannotExecuteView;

class ViewCompiler
{
	protected $config = [];
	protected $handlebars = null;
	protected $plugins = null;
	protected $cache = null;
	protected $flags = 0;
	protected $delimiters = ['{{', '}}'];

	public function __construct(array $config)
	{
		$this->config = $config;

		/* lightncandy handlebars compiler flags https://github.com/zordius/lightncandy#compile-options */
		$this->flags = $config['flags'] ?? LightnCandy::FLAG_ERROR_EXCEPTION | LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_RUNTIMEPARTIAL;

		$this->delimiters = $config['delimiters'] ?? $this->delimiters;

		$this->handlebars = $config['parent'];
		$this->plugins = $config['pluginsClass'];

		$this->cache = new Cache($this->config);
	}

	public function getView(string $viewPath)
	{
		$key = md5($viewPath);

		if (!$this->cache->get($key)) {
			/* file location validated in the parent class handlebars */
			$this->cache->save($key, $this->compile(FS::file_get_contents($viewPath), '/* ' . $viewPath . ' compiled @ ' . date('Y-m-d h:i:s e') . ' */'));
		}

		$templatePHP = $this->cache->include($key);

		/* is what we got back even executable? */
		if (!is_callable($templatePHP)) {
			throw new CannotExecuteView($key);
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
		$this->delimiters = (is_array($l)) ? $l : [$l, $r];
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
			'flags' => $this->flags, /* compiler flags */
			'helpers' => $this->plugins->getPlugins(),
			'renderex' => $comment,
			'delimiters' => $this->delimiters,
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
} /* end class */