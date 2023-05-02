<?php

ini_set('memory_limit', '3072M');
ignore_user_abort(TRUE);

require_once('../components/header.php');


$kingdoms_location = WORKING_LOCATION . 'kingdoms.json';
$ranks_location = WORKING_LOCATION . 'ranks.json';
$rows_location = WORKING_LOCATION . 'rows/';
$specify_ranks_location = '../static/csv/specify_ranks.csv';
$base_target_dir = WORKING_LOCATION . 'results/';


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
	($specify_ranks = file_get_contents($specify_ranks_location)) === FALSE ||
	count($specify_ranks = explode("\n", $specify_ranks)) == 0
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
	$export_type,
	[
		$include_common_names,
		$include_authors,
		$fill_in_links,
		$use_file_splitter,
	],
	$user_ip,
] = json_decode($_POST['payload'], TRUE);
$exclude_extinct_taxa = FALSE;

if(!$choice_tree)
	exit('Please select at least one tree node to proceed.');


//Make a list of required ranks
$required_ranks = [];
foreach($specify_ranks as $rank){

	$rank = explode(',', $rank);

	if(count($rank) != 1)
		$required_ranks[$rank[0]] = FALSE;

}


$subspecies_rank_id = FALSE;
foreach($ranks[$kingdom] as $rank_id => $rank_data){
  if($rank_data[0] == 'Subspecies')
    $subspecies_rank_id = $rank_id;
	if(array_key_exists($rank_data[0], $required_ranks))
		$required_ranks[$rank_data[0]] = $rank_id;
}

if($export_type == 'wizard'){
  $include_common_names = TRUE;
  $include_authors = TRUE;
  $fill_in_links = TRUE;
  $use_file_splitter = FALSE;
  $selected_ranks = array_values($required_ranks);
  $selected_ranks = array_map('strval', $selected_ranks);
  function truthy($val){
    return !!$val;
  }
  $selected_ranks = array_filter($selected_ranks,'truthy');
  if($subspecies_rank_id)
    $selected_ranks[] = $subspecies_rank_id;
}

$new_required_ranks = [];
foreach($required_ranks as $rank_name => $rank_id)
	if($rank_id !== FALSE)
		$new_required_ranks[] = $rank_id;

$required_ranks = $new_required_ranks;


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

$new_selected_ranks = [];
foreach($selected_ranks as $rank_name)
	$new_selected_ranks[] = $rank_name;
$selected_ranks = $new_selected_ranks;


//Output the header row
$line = [];
foreach($ranks[$kingdom] as $rank_id => $rank_data){

	if(!in_array($rank_id, $selected_ranks))
		continue;

	$rank_name = $rank_data[0];
	$line []= $rank_name;
	$line []= $rank_name.' GUID';

	if($include_authors)
		$line []= $rank_name . ' Author';

	if($include_common_names)
		$line []= $rank_name . ' Common Name';

	//if($fill_in_links)
		$line []= $rank_name . ' Source';

}

$header_line = implode($column_separator, $line) . $line_separator;

//send stats data
if(STATS_URL!=''){

	$friendly_selected_ranks = [];
	foreach($selected_ranks as $rank_id)
		$friendly_selected_ranks[] = $ranks[$kingdom][$rank_id][0];

	$stats_data = [
		'site'    => 'col',
		'tree'    => $choice_tree,
		'ranks'   => $friendly_selected_ranks,
		'ip'      => $user_ip,
		'options' => [
			'export_type' => $export_type,
			'include_common_names' => $include_common_names,
			'include_authors'      => $include_authors,
			'fill_in_links'        => $fill_in_links,
			'use_file_splitter'    => $use_file_splitter
		],
	];

	$options = [
		'http' => [
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($stats_data)
		]
	];
	$context = stream_context_create($options);
	file_get_contents(STATS_URL, FALSE, $context);
}


//Output the data
$result = '';
$lines_count = 0;
$file_id = 0;
$target_dir = '';

if($use_file_splitter)
	$line_limit = 7000;
else
	$line_limit = FALSE;


function compare_ranks($left, $right){
  global $tree;
  if ($tree[$left][1] == $tree[$right][1])
    return 0;
  return ($tree[$left][1] < $tree[$right][1]) ? -1 : 1;
}

function show_node(
	$taxon_number,
	$node,
	$parent_choice_tree = [],
	$parent_rank = FALSE,
	$line = []
){

	global $column_separator;
	global $line_separator;
	global $include_authors;
	global $include_common_names;
	global $kingdom;
	global $ranks;
	global $selected_ranks;
	global $fill_in_links;
	global $line_limit;
	global $lines_count;
	global $tree;
	global $exclude_extinct_taxa;
	global $header_line;
	global $result;

	$node_name = $node[0][0];
	$rank = $node[1];

	if(
		(//element was not chosen
			is_array($parent_choice_tree) &&
			!array_key_exists($node_name, $parent_choice_tree)
		)// ||
		//(//element is extinct and should not be displayed
		//	$node[0][4]==='true' &&
		//	$exclude_extinct_taxa
		//)
	)
		return;

	if($parent_choice_tree !== "true")
		$choice_tree = $parent_choice_tree[$node_name];
	else
		$choice_tree = "true";


  $local_result = '';
	if(in_array($rank, $selected_ranks)){//the rank of this element is selected

		if($parent_rank !== FALSE && $ranks[$kingdom][$rank][1] != $parent_rank){//if current $rank is not a direct parent of $parent_rank
		//var_dump('<br><br>',$node);
            $line = array_merge($line, handle_missing_ranks($rank, $parent_rank));

			}

		$line []= $node_name;
        $line []= $taxon_number;

		if($include_authors)
			$line []= $node[0][2];

		if($include_common_names)
			$line []= $node[0][1];

		if($fill_in_links)
			$line []= 'https://www.catalogueoflife.org/data/taxon/'. $taxon_number;
		else
			$line []= $node[0][3];

        $local_result .= implode($column_separator, $line) . $line_separator;

		$lines_count++;

	}

  if($line_limit !== FALSE && $lines_count >= $line_limit){
    $result .= $local_result;
    save_result();
  }
  

  usort($node[2], 'compare_ranks');
  $child_result = '';
	foreach($node[2] as $children_id)
		$child_result .= show_node($children_id, $tree[$children_id], $choice_tree, $rank, $line);

  if($line_limit === FALSE){
    if($child_result === '')
      return $local_result;
    else
      return $child_result;
  }
  else
    return '';

}

function handle_missing_ranks(
	$rank,
	$target_rank,
	$direct_call = TRUE
){

	global $kingdom;
	global $ranks;
	global $selected_ranks;
	global $include_authors;
	global $include_common_names;
	global $required_ranks;
	global $fill_in_links;

	if($rank == 0)
		return '';

	$line = [];
	if(!$direct_call && in_array($rank, $selected_ranks)){

		if(in_array($rank, $required_ranks))//show required && missing ranks
			$line[]= 'incertae sedis';
        else
            $line[] = '';

        // For GUID
		$line[] = '';

		if($include_authors)
            $line[] = '';

		if($include_common_names)
            $line[] = '';

		if($fill_in_links)
            $line[] = '';

	}

	$parent_rank = $ranks[$kingdom][$rank][1];

	if($parent_rank != $target_rank)
        $line = array_merge(handle_missing_ranks($parent_rank, $target_rank, FALSE), $line);

	return $line;

}

function save_result(){

	global $result;
	global $base_target_dir;
	global $file_id;
	global $header_line;
	global $target_dir;
	global $lines_count;

	$file_id++;

	if($result == '')
		return;

	if($target_dir == ''){

		do
			$target_dir = $base_target_dir . rand(0, time()) . '/';
		while(file_exists($target_dir));

		mkdir($target_dir);

	}

	file_put_contents($target_dir . 'tree_' . $file_id . '.csv', $header_line . $result);

	$result = '';
	$lines_count = 0;

	if($file_id > 500)
		exit('File limit reached');

}

$result .= show_node($kingdom, $tree[$kingdom], $choice_tree);


//output the result
$result_file_name = 'CoL ' . date('d.m.Y-H_m_i');
if($export_type == 'wizard'){

  $_FILES["file"] = array(
    "name" => $result_file_name.".csv",
    "raw" => $header_line . $result
  );
  require_once('../transform/index.php');

}
else if(DEBUG)
	echo $result;
else {

	save_result();


	if($file_id == 0)
		exit('There is no data to return');

	if($file_id == 1){//there is only one file to download

		$target_file = $target_dir . 'tree_1.csv';

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=" . $result_file_name . ".csv");
		header("Content-length: " . filesize($target_file));
		echo file_get_contents($target_file);

	}
	else {//zip the files

		$archive_name = $target_dir . 'tree.zip';

		$zip = new ZipArchive;

		if($zip->open($archive_name, ZipArchive::CREATE) !== TRUE)
			exit('Failed to zip files');

		foreach(glob($target_dir . '*.csv') as $file_name){

			$basename = explode("/", $file_name);
			$basename = end($basename);

			$zip->addFile($file_name, $basename);

		}

		$zip->close();

		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=" . $result_file_name . ".zip");
		header("Content-length: " . filesize($archive_name));

		echo file_get_contents($archive_name);


	}


	if($target_dir!==''){
		foreach (glob($target_dir.'*.*') as $file_name)
			unlink($file_name);

		rmdir($target_dir);
	}

}
