<?php

ini_set('memory_limit', '2048M');

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

$header_line .= $line_separator;


//Output the data
$result = '';
$lines_count = 0;
$file_id = 0;
$target_dir = '';

if($use_file_splitter)
	$line_limit = 7000;
else
	$line_limit = FALSE;


if($choice_tree==='file'){

	$result_tree = [];

	require_once('../components/compile.php');
	$file_content = file_get_contents('zip://'.$_FILES['file']['tmp_name'].'#taxa.txt');

	$result_tree = compile_kingdom(FALSE,$file_content);

}
else {

	$result_tree = [];

	foreach($choice_tree as $kingdom => $phylum_data){

		$file_content = json_decode(file_get_contents($compiled_path.$kingdom.$compiled_prefix),TRUE);

		if(is_string($phylum_data))
			$result_tree[$kingdom] = $file_content[$kingdom];

		else {

			$result_tree[$kingdom] = [[],$file_content[$kingdom][1]];

			foreach($phylum_data as $phylum => $class_data){

				if(is_string($class_data))
					$result_tree[$kingdom][0][$phylum] = $file_content[$kingdom][0][$phylum];

				else {

					$result_tree[$kingdom][$phylum] = [[],$file_content[$kingdom][0][$phylum][1]];

					foreach($class_data as $order => $order_data)
						if(is_string($order_data))
							$result_tree[$kingdom][0][$phylum][0][$order] = $file_content[$kingdom][0][$phylum][0][$order];

				}

			}

		}

	}
	unset($tree);
	unset($file_content);

}

foreach($result_tree as $kingdom => [$kingdom_data,$kingdom_id])
	foreach($kingdom_data as $phylum => [$phylum_data,$phylum_id])
		foreach($phylum_data as $class => [$class_data,$class_id])
			foreach($class_data as $order => [$order_data,$order_id])
				foreach($order_data as $family => [$family_data,$family_id])
					foreach($family_data as $genus => [$genus_data,$genus_id])
						foreach($genus_data as $species => [$species_author,$species_id]){

							$line = '';
							foreach($levels as $level){

								if($line!=='')
									$line .= $column_separator;

								if($level=='kingdom')
									$line .= ucfirst($$level);
								else
									$line .= $$level;

								if($fill_in_links){

									$id_field_name = $level.'_id';

									$line .= $column_separator;

									if($$id_field_name!=0)
										$line .= LINK.'redirect/?id='.$$id_field_name;

								}

							}

							if($include_authors)
								$line .= $column_separator.$species_author;

							if($include_common_names)
								$line .= $column_separator.$genus;

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



//output the result
if(DEBUG)
	echo $header_line.$result;
else {

	save_result();

	$result_file_name = 'Catalogue of Life '.date('d.m.Y-H_m_i');

	if($file_id==0)
		exit('There is no data to return');

	if($file_id==1){//there is only one file to download

		$target_file = $target_dir.'tree_1.csv';

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=".$result_file_name.".csv");
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
		header("Content-Disposition: attachment; filename=".$result_file_name.".zip");
		header("Content-length: " . filesize($archive_name));

		echo file_get_contents($archive_name);


	}


	foreach (glob($target_dir.'*.*') as $file_name)
		unlink($file_name);

	rmdir($target_dir);

}