<?php

ini_set('memory_limit', '512M');

require_once('../components/header.php');


$kingdoms_location = WORKING_LOCATION . 'kingdoms.json';
$ranks_location = WORKING_LOCATION . 'ranks.json';
$rows_location = WORKING_LOCATION . 'rows/';


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
		$include_sources
	]
] = json_decode($_POST['payload'], TRUE);

if(!$choice_tree)
	exit('No data specified.');


//Configuration
define('DEBUG', TRUE);

if(DEBUG){
	$column_separator = ",";
	$line_separator = "<br>";
}
else {
	$column_separator = "\t";
	$line_separator = "\n";
	header("Content-type: text/csv");
	header("Content-Disposition: attachment; filename=tree.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
}


//Output the header row
$line = '';
foreach($ranks[$kingdom] as $rank_data){

	if($line != '')
		$line .= $column_separator;

	$rank_name = ucfirst($rank_data[0]);

	$line .= $rank_name;

	if($include_authors)
		$line .= $column_separator . $rank_name . ' Author';

	if($include_common_names)
		$line .= $column_separator . $rank_name . ' Common Name';

	if($include_sources)
		$line .= $column_separator . $rank_name . ' Source';

}

echo $line . $line_separator;


//Output the data
function show_node(
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

	$node_name = $node[0][0];

	if(!is_array($parent_choice_tree) || !array_key_exists($node_name, $parent_choice_tree))
		return;

	if($parent_choice_tree !== "true")
		$choice_tree = $parent_choice_tree[$node_name];
	else
		$choice_tree = "true";


	//if($line!='')
	//	$line .= $column_separator;

	$rank = $node[1];

	if($ranks[$kingdom][$rank][1] != $parent_rank)//if current $rank is not a direct parent of $parent_rank
		$line .= handle_missing_ranks($rank, $parent_rank);
	//var_dump($rank,$parent_rank,handle_missing_ranks($rank,$parent_rank));

	$line .= $node[0][0];

	if($include_authors)
		$line .= $column_separator . $node[0][2];

	if($include_common_names)
		$line .= $column_separator . $node[0][1];

	if($include_sources)
		$line .= $column_separator . $node[0][3];

	echo $line . $line_separator;

	foreach($node[2] as $node_data)
		show_node($node_data, $choice_tree, $rank, $line);

}

function handle_missing_ranks(
	$rank,
	$target_rank
){

	global $kingdom;
	global $ranks;
	global $column_separator;
	global $include_authors;
	global $include_common_names;
	global $include_sources;

	$count = 1;

	if($include_authors)
		$count++;

	if($include_common_names)
		$count++;

	if($include_sources)
		$count++;

	$line = str_repeat($column_separator, $count);

	$parent_rank = $ranks[$kingdom][$rank][1];

	if($parent_rank != $target_rank)
		$line .= handle_missing_ranks($parent_rank, $target_rank);

	return $line;

}


foreach($tree as $node_data)
	show_node($node_data, $choice_tree);