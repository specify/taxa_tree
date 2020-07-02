<?php

$full_tree = [];

function compile_kingdom($kingdom){

	global $results_path;
	global $results_prefix;
	global $compiled_path;
	global $compiled_prefix;
	global $taxonomy_path;
	global $columns;
	global $full_tree;

	$source_path = $results_path.$kingdom.$results_prefix.$taxonomy_path;
	$target_path = $compiled_path.$kingdom.$compiled_prefix;

	$data = file_get_contents($source_path);
	$data = explode("\n",$data);
	unset($data[0]);

	//where taxonomicStatus = "accepted name"
	//kingdom > phylum > class > order > family > genus > specificEpithet > [genericName, scientificName, scientificNameAuthorship]

	$result = [];
	$full_tree[$kingdom] = [];

	foreach($data as $row){

		$row_data = explode("\t",$row);

		if($row=='' || $row_data[$columns['taxonomicStatus']]!=='accepted name')
			continue;

		$phylum = $row_data[$columns['phylum']];
		$class = $row_data[$columns['class']];
		$order = $row_data[$columns['order']];
		$family = $row_data[$columns['family']];
		$genus = $row_data[$columns['genus']];
		$specificEpithet = $row_data[$columns['specificEpithet']];
		$genericName = $row_data[$columns['genericName']];
		$scientificName = $row_data[$columns['scientificName']];
		$scientificNameAuthorship = $row_data[$columns['scientificNameAuthorship']];

		if(!array_key_exists($phylum,$result))
			$result[$phylum] = [];

		if(!array_key_exists($phylum,$full_tree[$kingdom]))
			$full_tree[$kingdom][$phylum] = [];

		if(!array_key_exists($class,$result[$phylum]))
			$result[$phylum][$class] = [];

		if(!array_key_exists($class,$full_tree[$kingdom][$phylum]))
			$full_tree[$kingdom][$phylum][$class] = [];

		if(!array_key_exists($order,$result[$phylum][$class]))
			$result[$phylum][$class][$order] = [];

		if(!in_array($order,$full_tree[$kingdom][$phylum][$class]))
			$full_tree[$kingdom][$phylum][$class][] = $order;

		if(!array_key_exists($family,$result[$phylum][$class][$order]))
			$result[$phylum][$class][$order][$family] = [];

		if(!array_key_exists($genus,$result[$phylum][$class][$order][$family]))
			$result[$phylum][$class][$order][$family][$genus] = [];

		if(!array_key_exists($specificEpithet,$result[$phylum][$class][$order][$family][$genus]))
			$result[$phylum][$class][$order][$family][$genus][$specificEpithet] = [$genericName,$scientificName,$scientificNameAuthorship];

	}

	$result = json_encode($result);
	file_put_contents($target_path,$result);

	if(!file_exists($target_path))
		alert('danger','Failed to create a tree for <b>'.$kingdom.'</b> at '. $target_path);

}

function compile_general_tree(){

	global $full_tree;
	global $tree_location;

	file_put_contents($tree_location,json_encode($full_tree));

	if(!file_exists($tree_location))
		alert('danger','Failed to create a full tree at '. $tree_location);

}