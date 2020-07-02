<?php

ini_set('memory_limit','512M');
$no_gui = TRUE;

require_once('../components/header.php');

if(!array_key_exists('payload',$_POST) || $_POST['payload']==''){
	header('Location: '.LINK);
	exit('No data specified');
}

[$tree,$include_generic_names_value,$include_scientific_names_value,$include_authors_value] = json_decode($_POST['payload'],TRUE);

if(!$tree){
	header('Location: '.LINK);
	exit('No data specified.');
}


//$result[$phylum][$class][$order][$family][$genus][$specificEpithet] = $payload;

$result_tree = [];
foreach($tree as $kingdom => $phylum_data){

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


header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=tree.csv");
header("Pragma: no-cache");
header("Expires: 0");


$result = '';
echo "Kingdom\tPhylum\tClass\tOrder\tFamily\tGenus\tSpecies";

if($include_generic_names_value)
	echo "\tSpecies Common Name";

if($include_scientific_names_value)
	echo "\tScientific Name";

if($include_authors_value)
	echo "\tSpecies Author";

echo "\n";

foreach($result_tree as $kingdom => $kingdom_data)//TODO: split data into several files and zip them
	foreach($kingdom_data as $phylum => $phylum_data)
		foreach($phylum_data as $class => $class_data)
			foreach($class_data as $order => $order_data)
				foreach($order_data as $family => $family_data)
					foreach($family_data as $genus => $genus_data)
						foreach($genus_data as $species => $species_data){

							$line = $kingdom."\t".$phylum."\t".$class."\t".$order."\t".$family."\t".$genus."\t".$species;

							if($include_generic_names_value)
								$line .= "\t".$species_data[0];

							if($include_scientific_names_value)
								$line .= "\t".$species_data[1];

							if($include_authors_value)
								$line .= "\t".$species_data[2];

							$result .= $line."\n";


						}

echo $result;