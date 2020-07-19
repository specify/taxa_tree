<?php

//TODO: delete this file

require_once('../components/header.php');
set_time_limit(500);

if(!file_exists(WORKING_LOCATION) && mkdir(WORKING_LOCATION) && !file_exists(WORKING_LOCATION))
	exit('Unable to create directory <i>' . WORKING_LOCATION . '</i>. Please check your config and permissions');


$kingdoms_data = WORKING_LOCATION . 'kingdoms.csv';
$ranks_data = WORKING_LOCATION . 'ranks.csv';
$rows_data = WORKING_LOCATION . 'rows.csv';

if(!file_exists($rows_data))
	exit('Run PYTHON script first to generate kingdoms.csv, ranks.csv and rows.csv');


# Memory management
unset($_COOKIE,$_POST,$_GET,$_SERVER,$_FILES);
ini_set('memory_limit','1024M');

# Read rows
$rows_file = fopen($rows_data, "r");
fgets($rows_file);
$rows = [];
$nodes = [];
$ranks = [];
$rank_ids = [];
$kingdoms = [];
$kingdom_ids = [];
$columns = array_flip(['tsn','name','common_name','parent_tsn','rank','kingdom','author','source']);
$i=0;

while(!feof($rows_file)){

	$row = fgets($rows_file);
	$row = substr($row, 0, -1);
	$row = explode("\t", $row);

	if(count($row) !== count($columns))
		continue;

	if($row[$columns['parent_tsn']]==0){//create kingdom

		$kingdom_id = $row[$columns['tsn']];
		$kingdoms[$kingdom_id] = $row[$columns['name']];
		$kingdom_ids = array_flip($kingdoms);

		$ranks[$kingdom_id] = [];
		$rows[$kingdom_id] = [];
		$nodes[$kingdom_id] = [];

	}
	else
		$kingdom_id = $kingdom_ids[$row[$columns['kingdom']]];

	if(!array_key_exists($row[$columns['rank']],$ranks[$kingdom_id])){//create rank
		$rank_id = $row[$columns['tsn']];
		$ranks[$kingdom_id][$rank_id] = $row[$columns['rank']];
		$rank_ids[$kingdom_id] = array_flip($ranks[$kingdom_id]);
	}
	else
		$rank_id = $rank_ids[$kingdom_id][$row[$columns['rank']]];

	if($row[$columns['common_name']] == $row[$columns['name']])
		$row[$columns['common_name']] = '';

	$data = [
		[
			$row[$columns['name']],
			$row[$columns['common_name']],
			$row[$columns['author']],
			$row[$columns['source']],
		],
		$rank_id,
		[],//children
		$row[$columns['parent_tsn']],
	];

	$rows[$kingdom_id][$row[$columns['tsn']]] = $data;

	if($row[$columns['parent_tsn']] == 0){
		unset($data[3]);
		$nodes[$kingdom_id][$row[$columns['tsn']]] = &$rows[$kingdom_id][$row[$columns['tsn']]];
	}

	$i++;

	if($i/1000==0)
		echo $i.'<br>';

}

fclose($rows_file);


file_put_contents($kingdoms_data,json_encode($kingdom_ids));
file_put_contents($ranks_data,json_encode($rank_ids));


foreach($rows as $kingdom_id => &$kingdom_data){

	do {
		$modified = FALSE;

		foreach($kingdom_data as $taxon_id => $row){

			if(!array_key_exists($row[3], $nodes[$kingdom_id]))
				continue;

			$parent = $row[3];
			unset($row[3]);

			$nodes[$kingdom_id][$parent][2][$taxon_id] = $row;
			$nodes[$kingdom_id][$taxon_id] = &$nodes[$kingdom_id][$parent][2][$taxon_id];
			unset($kingdom_data[$taxon_id]);

			$modified = TRUE;

		}

	} while($modified);

}

$rows_destination = WORKING_LOCATION.'rows/';
if(!file_exists($rows_destination))
	mkdir($rows_destination);

foreach($rows as $kingdom_id => $rows_data)
	file_put_contents($rows_destination . $kingdom_id.'.json', json_encode($rows_data));


if(DEVELOPMENT)
	exit('<br>Current RAM usage: ' . round(memory_get_usage() / 1024 / 1024, 2) .
	     'MB<br>Max RAM usage: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) .
	     'MB<br>RAM usage limit: ' . ini_get('memory_limit')
	);