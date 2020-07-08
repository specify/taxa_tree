<?php

ini_set('memory_limit', '512M');
ignore_user_abort(true);

require_once('../components/header.php');


$kingdoms_location = WORKING_LOCATION . 'kingdoms.json';
$ranks_location = WORKING_LOCATION . 'ranks.json';
$rows_location = WORKING_LOCATION . 'rows/';
$specify_ranks_location = '../static/csv/specify_ranks.csv';
$base_target_dir = WORKING_LOCATION.'results/';


//Get kingdoms
if(
	!file_exists($kingdoms_location) ||
	($kingdoms = file_get_contents($kingdoms_location)) === FALSE ||
	($kingdoms = json_decode($kingdoms, TRUE)) === FALSE
)
	exit('Can\'t read data from kingdoms.json');


//Get chosen kingdom
if(
	!array_key_exists('kingdom', $_GET) ||
	!array_key_exists($_GET['kingdom'], $kingdoms)
)
	exit('Please specify the kingdom');
$kingdom = $_GET['kingdom'];


//Get ranks
if(
	!file_exists($ranks_location) ||
	($ranks = file_get_contents($ranks_location)) === FALSE ||
	($ranks = json_decode($ranks, TRUE)) === FALSE ||
	!array_key_exists($kingdom, $ranks)
)
	exit('Can\'t read data from ranks.json');


//Get Specify ranks
if(
	!file_exists($specify_ranks_location) ||
	($specify_ranks=file_get_contents($specify_ranks_location))===FALSE ||
	count($specify_ranks=explode("\n",$specify_ranks))==0
)
	exit('Can\'t read data from specify_ranks.csv');


//Get rows
if(!file_exists($rows_location) ||
   ($tree = file_get_contents($rows_location . $kingdom . '.json')) === FALSE ||
   ($tree = json_decode($tree, TRUE)) === FALSE
)
	exit('Run data refresh first to generate the ' . $rows_location . $kingdom . '.json files');


//Get payload
if(!array_key_exists('payload', $_POST) || $_POST['payload'] == '')
	exit('No data specified');

[
	$choice_tree,
	$selected_ranks,
	[
		$include_common_names,
		$include_authors,
		$include_sources,
		$fill_in_itis_links
	]
] = json_decode($_POST['payload'], TRUE);

if(!$choice_tree)
	exit('Please select at least one tree node to proceed.');


//Make a list of required ranks
$required_ranks = [];
foreach($specify_ranks as $rank){

	$rank = explode(',',$rank);

	if(count($rank)!=1)
		$required_ranks[$rank[0]] = FALSE;

}


foreach($ranks[$kingdom] as $rank_id => $rank_data)
	if(array_key_exists($rank_data[0],$required_ranks))
		$required_ranks[$rank_data[0]] = $rank_id;

$new_required_ranks = [];
foreach($required_ranks as $rank_name => $rank_id)
	if($rank_id!==FALSE)
		$new_required_ranks[] = $rank_id;

$required_ranks = $new_required_ranks;

//TODO: fix a bug with ProtozoaGranuloreticulosea
//TODO: fix a bug with kingdoms not getting compiled
//TODO: implement a file splitter


//Configuration
define('DEBUG', FALSE);

if(DEBUG){
	$column_separator = ",";
	$line_separator = "<br>";
}
else {
	$column_separator = "\t";
	$line_separator = "\n";
}

if(!file_exists($base_target_dir))
	mkdir($base_target_dir);

$new_selected_ranks = [];
foreach($selected_ranks as $rank_name => $is_selected)
	if($is_selected)
		$new_selected_ranks[] = $rank_name;
$selected_ranks = $new_selected_ranks;


//Output the header row
$line = '';
foreach($ranks[$kingdom] as $rank_id => $rank_data){

	if(!in_array($rank_id,$selected_ranks))
		continue;

	if($line != '')
		$line .= $column_separator;

	$rank_name = $rank_data[0];
	$line .= $rank_name;

	if($include_authors)
		$line .= $column_separator . $rank_name . ' Author';

	if($include_common_names)
		$line .= $column_separator . $rank_name . ' Common Name';

	if($include_sources)
		$line .= $column_separator . $rank_name . ' Source';

}

$header_line = $line . $line_separator;

//Output the data

$result = '';
$line_limit = 7000;
$lines_count = 0;
$file_id = 1;
$target_dir = '';

function show_node(
	$taxon_number,
	$node,
	$parent_choice_tree = [],
	$parent_rank = 10,
	$line = ''
){

	global $column_separator;
	global $line_separator;
	global $include_authors;
	global $include_common_names;
	global $include_sources;
	global $kingdom;
	global $ranks;
	global $selected_ranks;
	global $fill_in_itis_links;
	global $result;
	global $line_limit;
	global $lines_count;

	$node_name = $node[0][0];
	$rank = $node[1];

	if(is_array($parent_choice_tree) && !array_key_exists($node_name, $parent_choice_tree))
		return;

	if($parent_choice_tree !== "true")
		$choice_tree = $parent_choice_tree[$node_name];
	else
		$choice_tree = "true";


	if(in_array($rank,$selected_ranks)){

		if($line!='')
			$line .= $column_separator;

		if($ranks[$kingdom][$rank][1] != $parent_rank)//if current $rank is not a direct parent of $parent_rank
			$line .= handle_missing_ranks($rank, $parent_rank);

		$line .= $node_name;

		if($include_authors)
			$line .= $column_separator . $node[0][2];

		if($include_common_names)
			$line .= $column_separator . $node[0][1];

		if($include_sources)
			if($fill_in_itis_links && $node[0][3]=='')
				$line .= 'https://itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value='.$taxon_number;
			else
				$line .= $column_separator . $node[0][3];

		$result .= $line . $line_separator;

		$lines_count++;

	}

	if($lines_count >= $line_limit)
		save_result();

	foreach($node[2] as $taxon_number => $node_data)
		show_node($taxon_number, $node_data, $choice_tree, $rank, $line);

}

function handle_missing_ranks(
	$rank,
	$target_rank,
	$direct_call=TRUE
){

	global $kingdom;
	global $ranks;
	global $selected_ranks;
	global $column_separator;
	global $include_authors;
	global $include_common_names;
	global $include_sources;
	global $required_ranks;

	$line = '';
	if(!$direct_call && in_array($rank,$selected_ranks)){

		if(in_array($rank,$required_ranks))//show required && missing ranks
			$line .= '(no '.$ranks[$kingdom][$rank][0].')';

		$count = 1;

		if($include_authors)
			$count++;

		if($include_common_names)
			$count++;

		if($include_sources)
			$count++;

		$line .= str_repeat($column_separator, $count);

	}

	$parent_rank = $ranks[$kingdom][$rank][1];

	if($parent_rank != $target_rank)
		$line .= handle_missing_ranks($parent_rank, $target_rank,FALSE);

	return $line;

}

function save_result(){

	global $result;
	global $base_target_dir;
	global $file_id;
	global $header_line;
	global $target_dir;
	global $lines_count;

	if($target_dir == ''){

		do
			$target_dir = $base_target_dir.rand(0,time()).'/';
		while(file_exists($target_dir));

		mkdir($target_dir);

	}

	file_put_contents($target_dir.'tree_'.$file_id.'.csv',$header_line.$result);

	$file_id++;
	$result = '';
	$lines_count = 0;

	if($file_id>5)//TODO: remove this
		exit('File limit reached');

}

foreach($tree as $taxon_number => $node_data)
	show_node($taxon_number, $node_data, $choice_tree);


//output the result
if(DEBUG)
	echo $result;
else {

	header("Pragma: no-cache");
	header("Expires: 0");

	if($file_id==1){//there is only one file to download

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=tree.csv");
		header("Content-length: " . filesize($result));
		echo file_get_contents($result);

	}
	else {//zip the files

		$archive_name = $target_dir.'tree.zip';

		$zip = new ZipArchive;

		if($zip -> open($archive_name, ZipArchive::CREATE ) !== TRUE)
			exit('Failed to zip files');

		foreach(glob($target_dir.'*.csv') as $file_name)
			$zip->addFile($file_name);

		$zip ->close();

		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=".$archive_name);
		header("Content-length: " . filesize($archive_name));


	}

	if (connection_aborted()){

		foreach (glob($target_dir.'*.*') as $file_name)
			unlink($file_name);

		rmdir($target_dir);

	}

}