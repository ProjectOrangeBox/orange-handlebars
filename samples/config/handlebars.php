<?php 

$config = [
	'compiled_folder'=>'application/views/compiled', /* where to store the compiled templates - a source code managed location is usually a good place */
	'cache_file'=>get_path('{cache}').'/handlebar.helpers.php', /* place to store all combined plugins so loading is faster */
	'template_extension'=>'hdl', /* handlebar template file extension */
	'template_folder'=>'views', /* where to search for handlebar templates in each package */
	'plugin_prefix'=>'Hbh_', /* plugins (helpers in handlebars) start with this */
	'development'=>true, /* always compile in developer mode */
	'save'=>true, /* save compiled templates or return it directly for immediate use? */
	'save_mode'=>'md5', /* "view" same place as template (view)+.php, "folders" in the {compiled_folder}+folder structure matching template view path+.php, "md5" in the {compiled_folder}+md5(matching template view path)+.php */
	'compile_if_doesnt_exist'=>false, /* always compile it if it doesn't exist */
];