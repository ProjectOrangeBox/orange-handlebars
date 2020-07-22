<?php

namespace Handlebars;

use Closure;
use Exception;
use Handlebars\compilers\ViewCompiler;
use Handlebars\compilers\PluginCompiler;
use projectorangebox\views\ViewsInterface;

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

class Handlebars implements ViewsInterface
{
	protected $views = [];
	protected $data = [];

	protected $viewCompiler = null;
	protected $pluginCompiler = null;

	/**
	 * Constructor - Sets Handlebars Preferences
	 *
	 * The constructor can be passed an array of config values
	 *
	 * @param	array	$userConfig = array()
	 */
	public function __construct(array $config = [])
	{
		$this->views = $config['views'] ?? [];

		if (is_array($config['data'])) {
			$this->data = $config['data'];
		}

		/* my classes */
		$this->pluginCompiler = new PluginCompiler($config);

		$this->viewCompiler = new ViewCompiler($config, $this->pluginCompiler, $this);
	}

	public function render(string $key, array $data = null): string
	{
		if (is_array($data)) {
			$this->data = array_replace($this->data, $data);
		}

		$phpFunction = $this->viewCompiler->getView($this->getView($key));

		return $phpFunction($this->data);
	}

	/**
	 * addView
	 *
	 * @param string $key
	 * @param string $path
	 * @return ViewsInterface
	 */
	public function addView(string $key, string $path): ViewsInterface
	{
		$this->views[trim(strtolower($key), '/')] = '/' . trim($path, '/');

		/* chain-able */
		return $this;
	}

	public function getViews(): array
	{
		return (array)$this->views;
	}

	/**
	 * data
	 *
	 * @param mixed $var
	 * @param mixed $value
	 * @return ViewsInterface
	 */
	public function data($key, $value = null): ViewsInterface
	{
		if (\is_array($key)) {
			$this->data = $key;
		} elseif (\is_string($key)) {
			$this->data[$key] = $value;
		}

		/* chain-able */
		return $this;
	}

	/**
	 * getData
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getData(string $key = null)
	{
		$data = null;

		if ($key) {
			if (isset($this->data[$key])) {
				$data = $this->data[$key];
			}
		} else {
			$data = $this->data;
		}

		return $data;
	}

	/**
	 * clearData
	 *
	 * @return ViewsInterface
	 */
	public function clearData(): ViewsInterface
	{
		$this->data = [];

		/* chain-able */
		return $this;
	}

	/*
	* set the template delimiters
	*
	* @param string/array
	* @param string
	* @return object (this)
	*/
	public function setDelimiters($l = '{{', string $r = '}}'): ViewsInterface
	{
		$this->viewCompiler->setDelimiters($l, $r);

		/* chain-able */
		return $this;
	}

	public function getView(string $name): string
	{
		$name = strtolower($name);

		if (!isset($this->views[$name])) {
			throw new Exception('View Not Found ' . $name);
		}

		$file = $this->views[$name];

		if (!\FS::file_exists($file)) {
			throw new Exception('View File Not Found ' . $this->views[$file]);
		}

		return $file;
	}

	public function addPlugin(string $name, Closure $closure): ViewsInterface
	{
		$this->pluginCompiler->addPlugin($name, $closure);

		/* chain-able */
		return $this;
	}

	public function addPlugins(array $plugins): ViewsInterface
	{
		foreach ($plugins as $name => $closure) {
			$this->addPlugin($name, $closure);
		}

		/* chain-able */
		return $this;
	}
} /* end class */
