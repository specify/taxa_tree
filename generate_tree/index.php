<?php

require_once('../config/required.php');
require_once('../config/optional.php');

if(!DEVELOPMENT || SHOW_ERRORS_IN_PRODUCTION){
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 1);
}

function alert($status,$message){

	echo '<div class="alert alert-'.$status.'">'.$message.'</div>';

	if($status=='danger')
		exit();

}

if(!file_exists(WORKING_LOCATION)){ // Create target directory

	mkdir(WORKING_LOCATION);

	if(!file_exists(WORKING_LOCATION))
		alert('danger','Unable to create directory <i>'.WORKING_LOCATION.'</i>. Please check your config and permissions');

}


if(!array_key_exists('file',$_FILES) || empty($_FILES['file']) || $_FILES['file']['error'] != 0){
	header('Location: '.LINK);
	alert('danger','No data specified');
}


do
	$file_name = rand(0,time());
while(file_exists(WORKING_LOCATION.$file_name.'/'));

$target_dir = WORKING_LOCATION.$file_name.'/';
$target_file_extension = explode('.', $_FILES['file']['name']);
$target_file_extension = end($target_file_extension);
$target_file = $_FILES['file']['tmp_name'];
$delete_target = $target_file;


if($target_file_extension=='zip'){

	$zip = new ZipArchive;
	$result = $zip->open($_FILES['file']['tmp_name']);
	if($result !== TRUE)
		alert('danger','Failed to open the archive');

	$zip->extractTo($target_dir);
	$zip->close();

	$file_name = glob($target_dir.'taxa_*.txt');
	$target_file = $file_name[0];
	$delete_target = $target_dir;

}

elseif($target_file_extension!='csv' && $target_file_extension!='txt')
	alert('danger','The uploaded file should be a .zip archive, a .csv or a .txt file');



$handle = fopen($target_file, "r");
if(!$handle)
	alert('danger','Failed to read the file');


$ranks = [
	'kingdom',
	'subkingdom',
	'infrakingdom',
	'superdivision',
	'division',
	'subdivision',
	'phylum',
	'subphylum',
	'superclass',
	'class',
	'subclass',
	'infraclass',
	'superorder',
	'order',
	'suborder',
	'infraorder',
	'superfamily',
	'family',
	'subfamily',
	'tribe',
	'subtribe',
	'genus',
	'subgenus',
	'section',
	'subsection',
	'species',
	'subspecies',
	'variety',
	'subvariety',
	'forma',
	'subforma',
];


$cols = fgets($handle);
$cols = explode("\t",$cols);

foreach($cols as &$col)
	$col = trim($col);


$cols = array_flip($cols);


$column_delimiter = "_";
$line_delimiter = "<br>";

//$column_delimiter = "\t";
//$line_delimiter = "\n";

//Set headers for file to be downloadable as CSV
//header("Content-type: text/csv");
//header("Content-Disposition: attachment; filename=tree.csv");
//header("Pragma: no-cache");
//header("Expires: 0");

//print the header line
$line = '';
foreach($ranks as $rank){

	if($line!='')
		$line .= $column_delimiter;

	$rank = ucfirst($rank);

	$line .= $rank.$column_delimiter.$rank.' Author';

}
echo $line.$line_delimiter;


//build the tree from raw CSV file
while(($line = fgets($handle)) !== false) {

	$line = explode("\t",$line);

	if($line[$cols['taxonomicStatus']]!=='accepted' && $line[$cols['taxonomicStatus']]!=='valid')
		continue;



	$parents = $line[$cols['higherClassification']];
	if(strlen($parents)>2)
		$parents .= '|';
	else
		$parents = '';
	$parents = explode('|',$parents);
	$parents = implode($column_delimiter.$column_delimiter,$parents);

	echo $parents.$line[$cols['scientificName']].$column_delimiter.$line[$cols['scientificNameAuthorship']];
	
}
fclose($handle);

function cleanup($target) {

	if (! is_dir($target))
		return unlink($target);

	if (substr($target, strlen($target) - 1, 1) != '/')
		$target .= '/';

	$files = glob($target . '*', GLOB_MARK);

	foreach ($files as $file)
		if (is_dir($file))
			cleanup($file);
		else
			unlink($file);

	return rmdir($target);

}
cleanup($delete_target);