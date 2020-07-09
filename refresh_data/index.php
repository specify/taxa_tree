<?php

require_once('../components/header.php');

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



prepare_dir($archives_path,FALSE);
prepare_dir($results_path,FALSE);
prepare_dir($compiled_path,FALSE);


//memory management
ini_set('memory_limit','2048M');
unset($_GET,$_POST,$_FILES,$_SERVER,$_COOKIE);


//prepare to compile data
$result = require_once('../components/compile.php');

//unzip the newest data and compile the tree
//$result = require_once('../components/update_database.php');
foreach($kingdoms as $kingdom)
	compile_kingdom($kingdom);

compile_general_tree();



alert('success','Success!');

alert('info','Current RAM usage: '.round(memory_get_usage()/1024/1024,2).
             'MB<br>Max RAM usage: '.round(memory_get_peak_usage()/1024/1024,2).
             'MB<br>RAM usage limit: '.ini_get('memory_limit'));