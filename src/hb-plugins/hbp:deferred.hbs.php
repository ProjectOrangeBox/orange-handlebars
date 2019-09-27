<?php

$helpers['hbp:deferred'] = function($options) {
	if (isset($options['hash']['key'])) {
		$key = $options['hash']['key'];
	} else {
		$key = $options['data']['root']['deferred'].uniqid('_s_',true);

		$s = new stdClass;

		$s->fn = $options['fn']();
		$s->_this = $options['_this'];

		file_put_contents(__ROOT__.'/var/cache/'.$key.'.txt',$key.chr(0).serialize($s));

		/* render the section -- !todo can we process this later??? */
		$html = $options['fn']($options['_this']);

		/* save it */
		file_put_contents(__ROOT__.'/var/cache/'.$key.'.deferred',$key.chr(0).$html);
	}

	return new \LightnCandy\SafeString('<i id="'.$key.'"></i>');
};
