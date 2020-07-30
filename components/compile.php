<?php

$full_tree = [];
$taxonomicStatus_to_accept = ['','accepted name','provisionally accepted name'];

function compile_kingdom($kingdom,$data_file){

	global $columns;
	global $taxonomicStatus_to_accept;
	global $compiled_path;
	global $compiled_prefix;

	$data_update = $kingdom!==FALSE;
	if($data_update)
		global $full_tree;

	//where taxonomicStatus in ["accepted name", "provisionally accepted name", ""]
	//kingdom > phylum > class > order > family > genus > specificEpithet > [genericName, scientificName, scientificNameAuthorship, isExtinct]

	$full_tree[$kingdom] = [];
	$result[$kingdom] = [[],0];

	$file = fopen($data_file,'r');
	fgets($file);

	while(! feof($file)){

		$row_data = explode("\t",fgets($file));

		if($kingdom===FALSE)
			$kingdom = $row_data[$columns['kingdom']];

		if(!in_array($row_data[$columns['taxonomicStatus']],$taxonomicStatus_to_accept))
			continue;

		$phylum = $row_data[$columns['phylum']];
		$class = $row_data[$columns['class']];
		$order = $row_data[$columns['order']];
		$family = $row_data[$columns['family']];
		$genus = $row_data[$columns['genus']];
		$specificEpithet = $row_data[$columns['specificEpithet']];
		$scientificNameAuthorship = $row_data[$columns['scientificNameAuthorship']];
		$identifier = $row_data[$columns['identifier']];


		if($phylum==''){
			$result[$kingdom][1] = $identifier;
			continue;
		}

		$kingdom_link = &$result[$kingdom][0];

		if(!array_key_exists($phylum,$kingdom_link))
			$kingdom_link[$phylum] = [[],0];

		if(!array_key_exists($phylum,$full_tree[$kingdom]))
			$full_tree[$kingdom][$phylum] = [];


		if($class==''){
			$kingdom_link[$phylum][1] = $identifier;
			continue;
		}

		$phylum_link = &$kingdom_link[$phylum][0];

		if(!array_key_exists($class,$phylum_link))
			$phylum_link[$class] = [[],0];

		if(!array_key_exists($class,$full_tree[$kingdom][$phylum]))
			$full_tree[$kingdom][$phylum][$class] = [];


		if($order==''){
			$kingdom_link[$class][1] = $identifier;
			continue;
		}

		$class_link = &$phylum_link[$class][0];

		if(!array_key_exists($order,$class_link))
			$class_link[$order] = [[],0];

		if(!in_array($order,$full_tree[$kingdom][$phylum][$class]))
			$full_tree[$kingdom][$phylum][$class][] = $order;


		if($family==''){
			$kingdom_link[$order][1] = $identifier;
			continue;
		}

		$order_link = &$class_link[$order][0];

		if(!array_key_exists($family,$order_link))
			$order_link[$family] = [[],0];


		if($genus==''){
			$kingdom_link[$family][1] = $identifier;
			continue;
		}

		$family_link = &$order_link[$family][0];

		if(!array_key_exists($genus,$family_link))
			$family_link[$genus] = [[],0];


		if($specificEpithet==''){
			$kingdom_link[$genus][1] = $identifier;
			continue;
		}

		$genus_link = &$family_link[$genus][0];

		if(!array_key_exists($specificEpithet,$genus_link))
			$genus_link[$specificEpithet] = [$scientificNameAuthorship,$identifier];

	}

	if($data_update===FALSE)
		return $result;

	$result = json_encode($result);

	$target_path = $compiled_path.$kingdom.$compiled_prefix;

	file_put_contents($target_path,$result);

	if(!file_exists($target_path))
		alert('danger','Failed to create a tree for <b>'.$kingdom.'</b> at '. $target_path);

	return TRUE;

}

function compile_general_tree(){

	global $full_tree;
	global $tree_location;

	file_put_contents($tree_location,json_encode($full_tree));

	if(!file_exists($tree_location))
		alert('danger','Failed to create a full tree at '. $tree_location);

}