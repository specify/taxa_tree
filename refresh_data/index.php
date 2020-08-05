<?php

require_once('../components/header.php');
#ignore_user_abort(TRUE);
set_time_limit(300);

function alert($status,$message){

	global $no_gui;

	if($no_gui){
		global $no_gui_separator;
		echo ucfirst($status).' : '.$message.$no_gui_separator;
	}
	else
		echo '<div class="alert alert-'.$status.'">'.$message.'</div>';

	if($status=='danger')
		exit();

}

function prepare_dir($dir,$delete_files=TRUE){

	if(!file_exists($dir)){

		mkdir($dir);

		if(!file_exists($dir))
			alert('danger','Unable to create directory <i>'.$dir.'</i>. Please check your config and permissions');
		elseif(VERBOSE)
			alert('secondary','Directory <i>'.$dir.'</i> was created successfully');

	} // Create target directory
	elseif($delete_files) { // Delete everything from that directory if not empty

		$files = glob($dir.'*.*');
		$files_count = count($files);

		foreach($files as $file)
			if(is_file($file))
				unlink($file);

		$files = glob($dir.'*.*');

		if(count($files) == 0){
			if($files_count==0)
				alert('info','<i>'.$dir.'</i> is already empty. No files deleted');
			else
				alert('info','Deleted <b>'.$files_count.'</b> files from <i>'.$dir.'</i>');
		}
		else
			foreach($files as $file)
				alert('danger','Failed to delete <b>'.$dir.$file.'</b>');

	}

}



prepare_dir($compiled_path,FALSE);


//memory management
ini_set('memory_limit','2048M');
unset($_POST,$_FILES,$_SERVER,$_COOKIE);


//prepare to compile data
require_once('../components/compile.php');

//unzip the newest data and compile the tree
//Check if versions changed
$new_version = file_get_contents($versions_page);
if(!array_key_exists('force',$_GET) &&  file_exists($versions_path) && $new_version === file_get_contents($versions_path)){
	alert('success','No need to update data');
	die;
}

file_put_contents($versions_path,$new_version);

foreach($kingdoms as $kingdom){

	$source_file = $suffix.$kingdom.$prefix;
	$temp_file = WORKING_LOCATION.'temp.zip';
	file_put_contents($temp_file,file_get_contents($source_file));
	compile_kingdom($kingdom,'zip://'.$temp_file.'#'.$taxonomy_path);

}

compile_general_tree();



alert('success','Success!');

alert('info','Current RAM usage: '.round(memory_get_usage()/1024/1024,2).
             'MB<br>Max RAM usage: '.round(memory_get_peak_usage()/1024/1024,2).
             'MB<br>RAM usage limit: '.ini_get('memory_limit'));