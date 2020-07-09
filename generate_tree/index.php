<?php

ini_set('memory_limit', '512M');
ignore_user_abort(true);

require_once('../components/header.php');


$base_target_dir = WORKING_LOCATION.'results/';


//Get payload
if(!array_key_exists('payload', $_POST) || $_POST['payload'] == '')
	exit('No data specified');

[
	$choice_tree,
	[
		$include_common_names,
		$include_authors,
		$fill_in_links,
		$use_file_splitter
	]
] = json_decode($_POST['payload'], TRUE);

if(!$choice_tree)
	exit('Please select at least one tree node to proceed.');



//Configuration
define('DEBUG', FALSE);

if(DEBUG){
	$column_separator = ",";
	$line_separator = "<br>";
}
else {
	$column_separator = "\t";
	$line_separator = "\n";

	header("Pragma: no-cache");
	header("Expires: 0");
}

if(!file_exists($base_target_dir))
	mkdir($base_target_dir);


//Output the header row
$header_line = '';

$levels = ['kingdom','phylum','class','order','family','genus','species'];

foreach($levels as $level){

	if($header_line!=='')
		$header_line .= $column_separator;

	$level_name = ucfirst($level);

	$header_line .= $level_name;

	if($fill_in_links)
		$header_line .= $column_separator.$level_name.' Source';

}

if($include_authors)
	$header_line .= $column_separator . 'Species Author';

if($include_common_names)
	$header_line .= $column_separator . 'Species Common Name';


//Output the data
$result = '';
$lines_count = 0;
$file_id = 0;
$target_dir = '';

if($use_file_splitter)
	$line_limit = 7000;
else
	$line_limit = FALSE;



$result_tree = [];
foreach($choice_tree as $kingdom => $phylum_data){

	$file_content = json_decode(file_get_contents($compiled_path.$kingdom.$compiled_prefix),TRUE);

	if(is_string($phylum_data))
		$result_tree[$kingdom] = $file_content;

	else {

		$result_tree[$kingdom] = [];

		foreach($phylum_data as $phylum => $class_data){

			if(is_string($class_data))
				$result_tree[$kingdom][$phylum] = $file_content[$phylum];

			else {

				$result_tree[$kingdom][$phylum] = [];

				foreach($class_data as $order => $order_data)
					if(is_string($order_data))
						$result_tree[$kingdom][$phylum][$order] = $file_content[$phylum][$order];

			}

		}

	}

}
unset($tree);
unset($file_content);


foreach($result_tree as $kingdom => $kingdom_data)
	foreach($kingdom_data as $phylum => $phylum_data)
		foreach($phylum_data as $class => $class_data)
			foreach($class_data as $order => $order_data)
				foreach($order_data as $family => $family_data)
					foreach($family_data as $genus => $genus_data)
						foreach($genus_data as $species => $species_data){

							$line = '';
							foreach($levels as $level){

								if($level!=='')
									$line .= $column_separator;

								$level_name = ucfirst($level);

								$header_line .= $$level_name;

								if($fill_in_links)
									$header_line .= $column_separator.LINK.'redirect/id?'.$species_data[2];

							}

							if($include_common_names)
								$line .= $column_separator.$species_data[0];

							if($include_authors)
								$line .= $column_separator.$species_data[1];

							$result .= $line.$line_separator;

							$lines_count++;

							if($line_limit !== FALSE && $lines_count >= $line_limit)
								save_result();


						}




function save_result(){

	global $result;
	global $base_target_dir;
	global $file_id;
	global $header_line;
	global $target_dir;
	global $lines_count;

	$file_id++;

	if($result=='')
		return;

	if($target_dir == ''){

		do
			$target_dir = $base_target_dir.rand(0,time()).'/';
		while(file_exists($target_dir));

		mkdir($target_dir);

	}

	file_put_contents($target_dir.'tree_'.$file_id.'.csv',$header_line.$result);

	$result = '';
	$lines_count = 0;

	if($file_id>200)
		exit('File limit reached');

}

foreach($tree as $taxon_number => $node_data)
	show_node($taxon_number, $node_data, $choice_tree);

save_result();


//output the result
if(DEBUG)
	echo $result;
else {

	if($file_id==0)
		exit('There is no data to return');

	if($file_id==1){//there is only one file to download

		$target_file = $target_dir.'tree_1.csv';

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=tree.csv");
		header("Content-length: " . filesize($target_file));
		echo file_get_contents($target_file);

	}
	else {//zip the files

		$archive_name = $target_dir.'tree.zip';

		$zip = new ZipArchive;

		if($zip -> open($archive_name, ZipArchive::CREATE ) !== TRUE)
			exit('Failed to zip files');

		foreach(glob($target_dir.'*.csv') as $file_name){

			$basename = explode("/",$file_name);
			$basename = end($basename);

			$zip->addFile($file_name,$basename);

		}

		$zip ->close();

		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=tree.zip");
		header("Content-length: " . filesize($archive_name));

		echo file_get_contents($archive_name);


	}


	foreach (glob($target_dir.'*.*') as $file_name)
		unlink($file_name);

	rmdir($target_dir);

}