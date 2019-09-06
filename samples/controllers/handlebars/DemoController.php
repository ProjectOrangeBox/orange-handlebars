<?php

class demoController extends APP_PublicController {
	
	public function __construct() {
		parent::__construct();
		
		$this->load->library('handlebars');
	}

	public function indexAction() {
		echo '<p><a href="/handlebars/demo/demo1">demo 1</a></p>';
		echo '<p><a href="/handlebars/demo/demo2">demo 2</a></p>';
		echo '<p><a href="/handlebars/demo/demo3">demo 3</a></p>';
	}

	public function demo1Action() {
		$this->benchmark->mark('handlebars start');
		$data = $this->get_data();

		// parse a file (view)
		$this->handlebars->parse('handlebars/demo/template.hdl',$data);

		$this->benchmark->mark('handlebars end');

		echo $this->benchmark->elapsed_time('handlebars start', 'handlebars end');
	}
	
	public function demo2Action() {
		$this->benchmark->mark('handlebars start');
		$text = file_get_contents(ROOTPATH.'/vendor/projectorangebox/handlebars/views/handlebars/demo/template.hdl');
		
		if ($text === false) {
			die('could not load ../views/handlebars/demo/template.hdl');
		}
		
		$data = $this->get_data();
		
		$this->handlebars
			// add a partial on the fly
			->add_partial('tester','<h3>{Hello from {{page_title}} add partial}</h3>')

			// add a plugin on the fly
			->add_plugin('bear',function($options) {
				return '['.$options['hash']['cookies'].']';
			})
	
			// parse a file (view)
			->parse_string($text,$data);

		$this->benchmark->mark('handlebars end');

		echo $this->benchmark->elapsed_time('handlebars start', 'handlebars end');
	}
	
	public function demo3Action() {
		$this->benchmark->mark('handlebars start');
		$data = $this->get_data();

		$this->handlebars
			# add a partial on the fly
			->add_partial('tester','<h3>{Hello from {{page_title}} add partial}</h3>')

			# add a plugin on the fly
			->add_plugin('bear',function($options) {
				return '['.$options['hash']['cookies'].']';
			})
	
			# parse a file (view)
			->parse('handlebars/demo/template.hdl',$data);

		$this->benchmark->mark('handlebars end');

		echo $this->benchmark->elapsed_time('handlebars start', 'handlebars end');
	}
	
	protected function get_data() {
		return array(
			'page_title'=>'Current Projects',
			'uppercase'=>'lowercase words',
			'projects'=>array(
				array(
					'name'=>'Acme Site',
					'assignees'=>array(
						array('name'=>'Dan','age'=>21),
						array('name'=>'Phil','age'=>12),
						array('name'=>'Don','age'=>34),
						array('name'=>'Pete','age'=>18),
					),
				),
				array(
					'name'=>'Lex',
					'contributors'=>array(
						array('name'=>'Dan','age'=>18),
						array('name'=>'Ziggy','age'=>16),
						array('name'=>'Jerel','age'=>7)
					),
				),
			),
		);
	}

} /* end controller */