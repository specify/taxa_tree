<?php

require_once('components/header.php');
head();
ini_set('memory_limit', '3072M');

$kingdoms_location = WORKING_LOCATION . 'kingdoms.json';
$ranks_location = WORKING_LOCATION . 'ranks.json';
$specify_ranks_location = 'static/csv/specify_ranks.csv';
$rows_location = WORKING_LOCATION.'rows/';

//Get kingdoms
if(
	!file_exists($kingdoms_location) ||
	($kingdoms=file_get_contents($kingdoms_location))===FALSE ||
	($kingdoms=json_decode($kingdoms, TRUE))===FALSE
)
	exit('Can\'t read data from kingdoms.json'); ?>


<h1>Taxonomic tree generator</h1> <?php

// Kingdom selection
if(!array_key_exists('kingdom',$_GET) || !array_key_exists($_GET['kingdom'],$kingdoms)){ ?>

	<h3>Step 1: Select the kingdom</h3>

	<ul> <?php

		foreach($kingdoms as $kingdom_id => $kingdom_name)
			echo '<li><a href="'.LINK.'?kingdom='.$kingdom_id.'">'.$kingdom_name.'</a></li>'; ?>

	</ul> <?php

	exit();

}
$kingdom = $_GET['kingdom'];


//Get the ranks
if(
	!file_exists($ranks_location) ||
	($ranks=file_get_contents($ranks_location))===FALSE ||
	($ranks=json_decode($ranks, TRUE))===FALSE ||
	!array_key_exists($kingdom,$ranks)
)
	exit('Can\'t read data from ranks.json');


//Get Specify ranks
if(
	!file_exists($specify_ranks_location) ||
	($specify_ranks=file_get_contents($specify_ranks_location))===FALSE ||
	count($specify_ranks=explode("\n",$specify_ranks))==0
)
	exit('Can\'t read data from specify_ranks.csv');


//Get the rows
if(!file_exists($rows_location) ||
   ($tree = file_get_contents($rows_location . $kingdom . '.json'))===FALSE ||
   ($tree=json_decode($tree, TRUE))===FALSE
)
	exit('Run data refresh first to generate the '.$rows_location . $kingdom.'.json files');


//Show the tree and other options ?>

<h3>Step 2: Select the nodes you want to have in your database</h3>

<ul class="pl-0" id="root"> <?php

	$display_down_to = 60;
	$arrow_location = LINK.'static/svg/arrow.svg';


	function show_node($node){

		global $display_down_to;
		global $ranks;
		global $kingdom;
		global $arrow_location;

		$node_name = $node[0][0];
		$capitalized_node_name = ucfirst($node_name);
		$rank_name = $ranks[$kingdom][$node[1]][0];
		$rank_id = $node[1];

		$show_children = $rank_id<$display_down_to;

		if($show_children)
			$collapse = '<button style="background-image: url('.$arrow_location.')" class="arrow"></button>';
		else
			$collapse = '';

		echo '<li data-name="'.$node_name.'">
			<button class="checkbox"></button>
			'.$collapse.
		    $capitalized_node_name.
	        '<span class="rank_indicator rank_'.$rank_id.'">'.$rank_name.'</span>';

		if($show_children){ ?>
			<ul class="collapsed"> <?php

				foreach($node[2] as $node_data)
					show_node($node_data); ?>

			</ul> <?php
		}

		echo '</li>';

	}

	foreach($tree as $node_data)
		show_node($node_data); ?>

</ul>

<h3>Step 3: Select the taxonomic levels that are present in your database</h3><?php

$new_specify_ranks = [];
foreach($specify_ranks as $rank){

	$rank = explode(',',$rank);
	$new_specify_ranks[$rank[0]] = count($rank)!=1;

}
$specify_ranks = $new_specify_ranks;


foreach($ranks[$kingdom] as $rank_id => $rank){

	if(!array_key_exists($rank[0],$specify_ranks))
		continue;

	$checked = '';
	if($specify_ranks[$rank[0]])
		$checked = ' checked'; ?>

	<div class="custom-control custom-checkbox">
		<input type="checkbox" class="custom-control-input rank" id="rank_<?=$rank_id?>" <?=$checked?>>
		<label class="custom-control-label" for="rank_<?=$rank_id?>"><?=ucfirst($rank[0])?></label>
	</div> <?php

} ?>

<h3>Step 4: Select which optional data should be present</h3>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_1">
	<label class="custom-control-label" for="option_1">Include Common Names</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_2">
	<label class="custom-control-label" for="option_2">Include Authors</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_3">
	<label class="custom-control-label" for="option_3">Include Sources</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_4">
	<label class="custom-control-label" for="option_4">Replace empty sources with links to <a href="https://itis.gov">itis.gov</a></label>
</div>

<div class="custom-control custom-checkbox mb-4">
	<input type="checkbox" class="custom-control-input option" id="option_5" checked>
	<label class="custom-control-label" for="option_5">Split the resulting tree into CSV files of less than 7000 records</label>
</div>

<form action="<?=LINK?>generate_tree/?kingdom=<?=$kingdom?>" method="POST" class="mt-4">
	<input type="hidden" id="payload_field" name="payload">
	<button class="btn btn-success btn-lg" id="get_result_button" type="submit">Get results</button>
</form>
<script src="<?=LINK?>static/js/main<?=JS?>"></script>