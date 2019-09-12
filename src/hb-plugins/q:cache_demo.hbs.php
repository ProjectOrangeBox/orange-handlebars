<?php

$helpers['q:cache_demo'] = function($options) {
	if (!$output = \handlebars\handlebars::cache($options)) {
		$output = 'Cached on: '.date('Y-m-d H:i:s').'  until '.date('Y-m-d H:i:s',strtotime('+'.(int)$options['hash']['cache'].' minutes'));

		ci()->handlebars->cache($options,$output);
	}

	return $output;
};
