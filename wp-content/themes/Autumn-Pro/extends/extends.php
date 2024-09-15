<?php
if($extend_handle = opendir(STYLESHEETPATH.'/extends')) {   
	while (($extend = readdir($extend_handle)) !== false) {
		if ($extend == '.' || $extend == '..' || is_file(STYLESHEETPATH.'/extends/'.$extend)) {
			continue;
		}
		
		if(is_file(STYLESHEETPATH.'/extends/'.$extend.'/'.$extend.'.php')){
			include STYLESHEETPATH.'/extends/'.$extend.'/'.$extend.'.php';
		}
	}   
	closedir($extend_handle);   
}