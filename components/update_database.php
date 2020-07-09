<?php


//Check if versions changed
$new_version = file_get_contents($versions_page);
if(file_exists($versions_path) && $new_version === file_get_contents($versions_path))
	return FALSE;

file_put_contents($versions_path,$new_version);

$made_changes = FALSE;
foreach($kingdoms as $kingdom){

	//download the file
	$source_file = $suffix.$kingdom.$prefix;
	$file_content = file_get_contents($source_file);

	if($file_content === FALSE)
		alert('danger', 'Failed to download the zip file');

	$archive_path = $archives_path.$kingdom.$file_prefix;

	if(file_exists($archive_path) && $file_content === file_get_contents($archive_path))
		continue;

	file_put_contents($archive_path, $file_content);

	unset($file_content);


	//unzip the file
	$zip = new ZipArchive;
	$result = $zip->open($archive_path);
	if($result !== TRUE)
		alert('danger','Failed to extract the following archive: <b>'.$archive_path.'</b>');

	$zip->extractTo($results_path.$kingdom.$results_prefix);
	$zip->close();


	if(VERBOSE)
		alert('secondary','File '.$source_file.' was downloaded and extracted to <b>'.$file_content.'</b> successfully');

	compile_kingdom($kingdom);

}



return TRUE;