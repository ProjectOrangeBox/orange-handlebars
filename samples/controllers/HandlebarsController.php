<?php
/**
* Compile all found .hdl files
*
* This content is released under the MIT License (MIT)
*
* @package	CodeIgniter / Orange
* @author	Don Myers
* @license http://opensource.org/licenses/MIT MIT License
* @link	https://github.com/ProjectOrangeBox
*
*
* php index.php handlebars/compile
*/

class HandlebarsController extends O_CliController {

	public function compileCliAction($ext=null) {
		$this->load->library(['handlebars','package_manager']);

		$ext = $this->handlebars->template_extension();

		/* get all of the packages details */
		$packages = ci()->package_manager->prepare();

		$template_path = $this->handlebars->template_folder();

		$files = [];

		/* find the active packages */
		foreach ($packages as $p) {
			if ($p['is_active']) {
				$search = ROOTPATH.$p['database']['full_path'].'/'.$template_path;

				$templates = (array)$this->package_manager->rglob($search,'*.'.$ext);

				foreach ($templates as $t) {
					$files[$t] = str_replace(ROOTPATH.$p['database']['full_path'].'/','',$t);
				}
			}
		}

		$s = (count($files) > 1) ? 's' : '';

		$this->handlebars
			->compiled_folder('application/views/compiled')
			->development(true);

		$this->output('<cyan>Files matching extension: <off>.'.$ext);
		$this->output('<cyan>Helpers starting with <off>'.ci()->handlebars->plugin_prefix());
		$this->output('<cyan>Compile to: <off>'.ci()->handlebars->compiled_folder());
		$this->output('<cyan>In Package Folders: <off>'.ci()->handlebars->template_folder());
		$this->output('<green>Found '.count($files).' file'.$s.':');

		foreach ($files as $full=>$template) {
			$this->output('<yellow>Compiling: <off>'.$template);

			$template_php = $this->handlebars->compile(file_get_contents($full),$template);

			/*
			since we are not stopping then it will return nothing on error
			only write out when we have something
			*/
			if (!empty($template_php)) {
				$this->handlebars->save_compile_file(ci()->handlebars->prepare_compiler_path($template),$template_php);
			}
		}

		$this->output('<green>Done');
	}

} /* end class */