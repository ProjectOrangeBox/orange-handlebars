<?php

namespace Handlebars;

use Exception;

/**
 * Handlebars Parser
 *
 * This content is released under the MIT License (MIT)
 *
 * @package	CodeIgniter / Orange
 * @author	Don Myers
 * @author Zordius, Taipei, Taiwan
 * @license http://opensource.org/licenses/MIT MIT License
 * @link	https://github.com/ProjectOrangeBox
 * @link https://github.com/zordius/lightncandy
 *
 *
 *
 * Helpers:
 *
 * $helpers['foobar'] = function($options) {};
 *
 * $options =>
 * 	[name] => lex_lowercase # helper name
 * 	[hash] => Array # key value pair
 * 		[size] => 123
 * 		[fullname] => Don Myers
 * 	[contexts] => ... # full context as object
 * 	[_this] => Array # current loop context
 * 		[name] => John
 * 		[phone] => 933.1232
 * 		[age] => 21
 * 	['fn']($options['_this']) # if ??? - don't forget to send in the context
 * 	['inverse']($options['_this']) # else ???- don't forget to send in the context
 *
 * external functions used
 * path() - combined a config file key/value with some {magic} find and replace
 * env()
 * atomic_file_put_contents() - atomic version of file_put_contents()
 * ci('config')->item(...)
 * ci('servicelocator')->find(...)
 *
 */

use Handlebars\Add;
use LightnCandy\LightnCandy;

class Handlebars
{
	protected $config = [];

	protected $plugins = []; /* loaded helpers */
	protected $partials = []; /* embedded partials text - used by add class */
	protected $templates = []; /* dynamically added templates as paths - also used by the add class */

	protected $add = null; /* place holder folder add sub class for chainability */

	/**
	 * Constructor - Sets Handlebars Preferences
	 *
	 * The constructor can be passed an array of config values
	 *
	 * @param	array	$userConfig = array()
	 */
	public function __construct(array $config = [])
	{
		$requiredDefaults = [
			'forceCompile' => (env('DEBUG') == 'development'), /* boolean - always compile in developer mode */
			'templatePrefix' => 'hbs_templates', /* string - service locator type - must be lowercase */
			'pluginPrefix' => 'hbs_plugin', /* string - service locator type - must be lowercase */
			'cacheFolder' => path('{cache}').'handlebars', /* string - folder inside cache folder if any */
			'HBCachePrefix' => 'hbs.', /* string - prefix all HBCache cached entries with */
			'delimiters' => ['{{', '}}'], /* array */
			/* lightncandy handlebars compiler flags https://github.com/zordius/lightncandy#compile-options */
			'flags' => LightnCandy::FLAG_ERROR_EXCEPTION | LightnCandy::FLAG_HANDLEBARS | LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_BESTPERFORMANCE | LightnCandy::FLAG_RUNTIMEPARTIAL, /* integer */
		];

		$this->config = ci('config')->merged('handlebars',$requiredDefaults,$config);

		require_once 'HBCache.php';

		\HBCache::setPrefix($this->config['HBCachePrefix']);

		/* allow handlebars->add->... chaining */
		$this->add = new Add($this,$plugins,$partials,$templates);

		$this->makeCacheFolder($this->config['cacheFolder']);
	}

	/* These are just like CodeIgniter regular parser */

	/**
	 * Parse a template
	 *
	 * Parses pseudo-variables contained in the specified template view,
	 * replacing them with the data in the second param
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	public function parse(string $templateFile, array $data = [], bool $return = false): string
	{
		return $this->hbsRun($this->hbsParse($templateFile, true), $data, !$return);
	}

	/**
	 * Parse a String
	 *
	 * Parses pseudo-variables contained in the specified string,
	 * replacing them with the data in the second param
	 *
	 * @param	string
	 * @param	array
	 * @param	bool
	 * @return	string
	 */
	public function parse_string(string $templateStr, array $data = [], bool $return = false): string
	{
		return $this->hbsRun($this->hbsParse($templateStr, false), $data, !$return);
	}

	/*
	* set the template delimiters
	*
	* @param string/array
	* @param string
	* @return object (this)
	*/
	public function set_delimiters(/* string|array */$l = '{{', string $r = '}}'): Handlebars
	{
		/* set delimiters */
		$this->config['delimiters'] = (is_array($l)) ? $l : [$l, $r];

		/* chain-able */
		return $this;
	}

	/* handlebars library specific methods */

	/**
	 * heavy lifter - wrapper for lightncandy https://github.com/zordius/lightncandy handlebars compiler
	 *
	 * returns raw compiled_php as string or prepared (executable) php
	 *
	 * @param string
	 * @param string
	 * @param boolean
	 * @return string / closure
	 */
	public function compile(string $templateSource, string $comment = ''): string
	{
		/* get our helpers if there aren't already loaded */
		$this->loadHelpers();

		/* compile it into php magic! */
		return LightnCandy::compile($templateSource, [
			'flags' => $this->config['flags'], /* load our "compiled" helpers */
			'helpers' => $this->plugins, /* add this to the compiled file for reference */
			'renderex' => '/* ' . $comment . ' compiled @ ' . date('Y-m-d h:i:s e') . ' */', /* added to compiled PHP */
			'partialresolver' => function ($context, $name) { /* include / partial handler */
				/* default */
				$template = '<!-- template "' . $name . '" not found !>';

				try {
					$template = $this->fileGetContents($this->findTemplate($name));
				} catch (Exception $e) { /* If the template isn't found it will use the default already set */ }

				return $template;
			},
			'delimiters' => $this->config['delimiters'],
			/*
			partial templates attached directly - these are a little to hard to use in cli compile all mode but,
			since this library supports this feature I figured I should added it just to be complete
			*/
			'partials' => $this->partials,
		]);
	}

	/* search all php include paths for a template file
	* findTemplate(/view/template_folder/handle_bars.tmpl);
	*
	* @param string
	* @return vold/string
	*/
	public function findTemplate(string $name): string
	{
		$name = strtolower($name);

		/* when we try to load a template which doesn't exist it wil throw an error */
		return (isset($this->templates[$name])) ? $this->templates[$name] : ci('servicelocator')->find($this->config['templatePrefix'],$name);
	}

	/*
	* save a compiled file
	*
	* @param string
	* @param string
	* @return boolean
	*/
	public function saveCompileFile(string $compiledFile, string $templatePhp): int
	{
		/* write out the compiled file */
		return \atomic_file_put_contents($compiledFile, '<?php ' . $templatePhp . '?>');
	}

	/**
	 * hbsParse
	 *
	 * @param string $template
	 * @param bool $isFile
	 * @return void
	 */
	public function hbsParse(string $template, bool $isFile): string
	{
		/* build the compiled file path */
		$compiledFile = $this->config['cacheFolder'] . '/' . md5($template) . '.php';

		/* always compile in development or not save or compile if doesn't exist */
		if ($this->config['forceCompile'] || !file_exists($compiledFile)) {
			/* compile the template as either file or string */
			if ($isFile) {
				$source = $this->fileGetContents($this->findTemplate($template));
				$comment = $template;
			} else {
				$source = $template;
				$comment = 'parse_string_' . md5($template);
			}

			$this->saveCompileFile($compiledFile, $this->compile($source, $comment));
		}

		return $compiledFile;
	}

	/**
	 * hbsRun
	 *
	 * @param string $compiledFile
	 * @param array $data
	 * @param bool $appendOutput
	 * @return void
	 */
	public function hbsRun(string $compiledFile, array $data, bool $appendOutput): string
	{
		/* did we find this template? */
		if (!file_exists($compiledFile)) {
			/* nope! - fatal error! */
			throw new Exception('Could not locate compiled handlebars file ' . $compiledFile);
		}

		/* yes include it */
		$templatePHP = include $compiledFile;

		/* is what we loaded even executable? */
		if (!is_callable($templatePHP)) {
			throw new Exception('Could not execute template');
		}

		/* send data into the magic void... */
		$output = $templatePHP($data);

		/* Should we append to output? */
		if ($appendOutput) {
			ci('output')->append_output($output);
		}

		return $output;
	}

	/**
	 * loadHelpers
	 *
	 * @return void
	 */
	protected function loadHelpers(): void
	{
		$cacheFile = $this->config['cacheFolder'] . '/handlebar.helpers.php';

		if ($this->config['forceCompile'] || !file_exists($cacheFile)) {
			$combined  = '<?php' . PHP_EOL . '/*' . PHP_EOL . 'DO NOT MODIFY THIS FILE' . PHP_EOL . 'Written: ' . date('Y-m-d H:i:s T') . PHP_EOL . '*/' . PHP_EOL . PHP_EOL;

			$plugins = ci('config')->item('services.'.$this->config['pluginPrefix']);

			/* find all of the plugin "services" */
			if (\is_array($plugins)) {
				foreach ($plugins as $service => $path) {
					$pluginSource  = php_strip_whitespace(__ROOT__.$path);
					$pluginSource  = trim(str_replace(['<?php', '<?','?>'],'',$pluginSource));
					$pluginSource  = trim('/* ' . $path . ' */'.PHP_EOL.$pluginSource).PHP_EOL.PHP_EOL;

					$combined .= $pluginSource;
				}
			}

			/* save to the cache folder on this machine (in a multi-machine env each will just recreate this locally) */
			\atomic_file_put_contents($cacheFile, trim($combined));
		}

		/* start with empty array */
		$helpers = [];

		/* include the combined file */
		include $cacheFile;

		/* assign it to the class property directly attached over loaded */
		$this->plugins = array_merge($helpers, $this->plugins);
	}

	/**
	 * fileGetContents
	 *
	 * @param string $file
	 * @return void
	 */
	protected function fileGetContents(string $file): string
	{
		$absolutePath = __ROOT__ . $file;

		if (!\file_exists($absolutePath)) {
			throw new Exception('Can not location the file "' . $absolutePath . '".');
		}

		return \file_get_contents($absolutePath);
	}

	public function makeCacheFolder(string $folder): void
	{
		/* let's make sure the compile folder is there before we try to save the compiled file! */
		if (!\file_exists($folder)) {
			mkdir($folder, 0755, true);
		}

		/* is the folder writable by us? */
		if (!\is_writable($folder)) {
			throw new Exception('Cannot write to folder ' . $folder);
		}
	}

} /* end class */
