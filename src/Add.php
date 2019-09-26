<?php

namespace Handlebars;

use Closure;

class Add {
	protected $parent;
	protected $partials;
	protected $templates;
	protected $plugins;

	public function __construct($parent,&$partials,&$templates,&$plugins)
	{
		$this->parent = $parent;
		$this->partials = &$partials;
		$this->templates = &$templates;
		$this->plugins = &$plugins;
	}

	/*
	* add partials as internal strings (faster than searching) as single string or array
	*
	* @param string/array
	* @param string
	* @return object (this)
	*/
	public function partials(array $partials) : handlebars
	{
		foreach ($partials as $name=>$template) {
			$this->partial($name,$template);
		}

		return $this->parent;
	}

	/**
	 * partial
	 *
	 * @param string $name
	 * @param string $template
	 * @return void
	 */
	public function partial(string $name,string $template) : handlebars
	{
		$this->partials[$name] = $template;

		/* chain-able */
		return $this->parent;
	}

public function templates(array $templates) : handlebars
	{
		foreach ($templates as $name=>$path) {
			$this->template($name,$path);
		}

		return $this->parent;
	}

	/**
	 * partial
	 *
	 * @param string $name
	 * @param string $template
	 * @return void
	 */
	public function template(string $name,string $path) : handlebars
	{
		$this->templates[$name] = $path;

		/* chain-able */
		return $this->parent;
	}


	/*
	* add handlebar helpers as single string or array
	*
	* @param string/array
	* @param string
	* @return object (this)
	*/
	public function plugins(array $plugins) : handlebars
	{
		foreach ($plugins as $name=>$closure) {
			$this->plugin($name,$closure);
		}

		return $this->parent;
	}

	/**
	 * plugin
	 *
	 * @param string $name
	 * @param Closure $closure
	 * @return void
	 */
	public function plugin(string $name,Closure $closure) : handlebars
	{
		$this->plugins[$name] = $closure;

		/* chain-able */
		return $this->parent;
	}

} /* end class */
