<?php

$handle = fopen($target_file, "r");
if(!$handle)
	die;

$columns = fgets($handle);
$columns = trim($columns);
$columns = explode($column_separator,$columns);
$columns = array_flip($columns);

$columns_to_rename = [
	'Division' => 'Phylum',
	'Species GUID' => 'Species LSID',
	'Subspecies GUID' => 'Subspecies LSID',
];

foreach($columns_to_rename as $old_name => $new_name)
	if(array_key_exists($old_name,$columns)){
		$columns[$new_name] = $columns[$old_name];
		unset($columns[$old_name]);
	}


$results_array = [
	'Kingdom',
//	'Kingdom Author',
//	'Kingdom Common Name',
//	'Kingdom Source',
	'Phylum',
//	'Phylum Author',
//	'Phylum Common Name',
//	'Phylum Source',
	'Class',
//	'Class Author',
//	'Class Common Name',
//	'Class Source',
	'Order',
//	'Order Author',
//	'Order Common Name',
//	'Order Source',
	'Family',
//	'Family Author',
//	'Family Source',
	'Genus',
//	'Genus Author',
//	'Genus Common Name',
//	'Genus Source',
	'Species',
	'Subspecies',
	'Family Common Name',
	'Species Author',
	'Species Source',
	'Species LSID',
	'Species Common Name',
	'Subspecies Author',
	'Subspecies Source',
	'Subspecies LSID',
	'Subspecies Common Name',
];

$results = implode($column_separator,$results_array);
$header = strtolower($results);
echo $header.$line_separator;

$results_array = explode($column_separator,$results);

while (($line = fgets($handle)) !== false) {

	$line = trim($line);

	$line = explode($column_separator,$line);

	$result_line = '';
	foreach($results_array as $result){

		if($result_line!='')
			$result_line .= $column_separator;

		if(array_key_exists($result,$columns) && array_key_exists($columns[$result],$line))
			$result_line .= $line[$columns[$result]];

	}

	$result_line .= $line_separator;

	echo $result_line;

}

fclose($handle);