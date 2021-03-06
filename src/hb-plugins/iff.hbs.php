<?php

$helpers['iif'] = function($value1,$op,$value2,$options) {
	/*
	{{#iif page_title "=" "Current Projects"}}
		True Do This
	{{else}}
		False Do This
	{{/iif}}
	*/

	$return = '';

	switch ($op) {
		case '=';
			if ($value1 == $value2) {
				$return = $options['fn']();
			} elseif ($options['inverse'] instanceof \Closure) {
				$return = $options['inverse']();
			}
		break;
		case '>';
			if ($value1 > $value2) {
				$return = $options['fn']();
			} elseif ($options['inverse'] instanceof \Closure) {
				$return = $options['inverse']();
			}
		break;
		case '<';
			if ($value1 < $value2) {
				$return = $options['fn']();
			} elseif ($options['inverse'] instanceof \Closure) {
				$return = $options['inverse']();
			}
		break;
		case '!=';
		case '<>';
			if ($value1 != $value2) {
				$return = $options['fn']();
			} elseif ($options['inverse'] instanceof \Closure) {
				$return = $options['inverse']();
			}
		break;
		case '>=';
		case '=>';
			if ($value1 >= $value2) {
				$return = $options['fn']();
			} elseif ($options['inverse'] instanceof \Closure) {
				$return = $options['inverse']();
			}
		break;
		case '<=';
		case '=<';
			if ($value1 <= $value2) {
				$return = $options['fn']();
			} elseif ($options['inverse'] instanceof \Closure) {
				$return = $options['inverse']();
			}
		break;
	}

	return $return;
};
