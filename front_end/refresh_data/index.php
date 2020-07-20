<?php

require_once('../components/header.php');

if(!file_exists(WORKING_LOCATION) && mkdir(WORKING_LOCATION) && !file_exists(WORKING_LOCATION))
	exit('Unable to create directory <i>' . WORKING_LOCATION . '</i>. Please check your config and permissions');


$kingdoms_data = WORKING_LOCATION . 'kingdoms.csv';
$ranks_data = WORKING_LOCATION . 'ranks.csv';
$rows_data = WORKING_LOCATION . 'rows.csv';

if(!file_exists($kingdoms_data) || !file_exists($ranks_data) || !file_exists($rows_data))
	exit('Run PYTHON script first to generate kingdoms.csv, ranks.csv and rows.csv');


# Read kingdoms
$kingdoms_file = fopen($kingdoms_data, "r");
fgets($kingdoms_file);
$kingdoms = [];

while(!feof($kingdoms_file)){

	$row = fgets($kingdoms_file);
	$row = substr($row, 0, -1);
	$row = explode("\t", $row);

	if(count($row) == 2)
		$kingdoms[$row[0]] = $row[1];

}

fclose($kingdoms_file);
file_put_contents(WORKING_LOCATION . 'kingdoms.json', json_encode($kingdoms));
unset($kingdoms);


# Read ranks
$ranks_file = fopen($ranks_data, "r");
fgets($ranks_file);
$ranks = [];

while(!feof($ranks_file)){

	$row = fgets($ranks_file);
	$row = substr($row, 0, -1);
	$row = explode("\t", $row);

	if(count($row) !== 4)
		continue;

	if(!array_key_exists($row[0], $ranks))
		$ranks[$row[0]] = [];

	$ranks[$row[0]][$row[1]] = [$row[2], $row[3]];
}

fclose($ranks_file);
file_put_contents(WORKING_LOCATION . 'ranks.json', json_encode($ranks));
unset($ranks);


# Memory management
unset($_COOKIE,$_POST,$_GET,$_SERVER,$_FILES);
ini_set('memory_limit','1024M');

# Read rows
//tsn name common_name parent_tsn rank kingdom author source
$rows_file = fopen($rows_data, "r");
fgets($rows_file);
$rows = [];
$nodes = [];

while(!feof($rows_file)){

	$row = fgets($rows_file);
	$row = substr($row, 0, -1);
	$row = explode("\t", $row);

	if(count($row) !== 8)
		continue;

	if(array_key_exists([$row[0]],$rows[$row[5]])){
		$rows[$row[5]][$row[0]][0][1] .= ', '.$row[2];
		continue;
	}

	if(!array_key_exists($row[5], $rows)){//create kingdom if does not exist
		$rows[$row[5]] = [];
		$nodes[$row[5]] = [];
	}

	if($row[6] == "NULL")
		$row[6] = '';

	if($row[7] == "NULL")
		$row[7] = '';

	if($row[1] == $row[2])
		$row[2] = '';

	$data = [
		[
			$row[1],//name
			$row[2],//common name
			$row[6],//author
			$row[7],//source
		],
		$row[4],//rank_id
		[],//children
		$row[3],//parent
	];

	$rows[$row[5]][$row[0]] = $data;

	if($row[3] == 0){
		unset($data[3]);
		$nodes[$row[5]][$row[0]] = &$rows[$row[5]][$row[0]];
	}

}

fclose($rows_file);


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