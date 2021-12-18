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


<h1>Taxonomic tree generator</h1>
<h2>Data provided by <a target="_blank" href="https://www.marinespecies.org/">
  WoRMS</a></h2>
<p>
  WoRMS Editorial Board (2021). World Register of Marine Species. Available from
  https://www.marinespecies.org at VLIZ. Accessed [date].
  http://doi.org/10.14284/170
</p>

<?php
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



//Show the tree and other options

if($kingdom==8)
	$display_down_to = 'Family';
else
	$display_down_to = 'Class'; ?>

<h3>Step 2: Choose export data type</h3>
<label>
  <input type="radio" name="format" value="wizard">
  Export for SpWizard
</label>
<br>
<label>
  <input type="radio" name="format" value="workbench" checked>
  Export for Specify Workbench
</label>

<h3>Step 3: Select the nodes you want to have in your database</h3>

<ul class="pl-0" id="root"> <?php

	$stop_rank = FALSE;
	$parent_rank_id = 0;

	foreach($ranks[$kingdom] as $rank_id => &$rank_data){

		if($rank_data[0]==$display_down_to)
			$stop_rank = $rank_id;

		if($parent_rank_id!=0)
			$ranks[$kingdom][$parent_rank_id][2] = $rank_id;

		$parent_rank_id = $rank_id;

	}

	$arrow_location = LINK.'static/svg/arrow.svg';


	function show_node($node){

		global $stop_rank;
		global $ranks;
		global $kingdom;
		global $arrow_location;
		global $tree;


		$node_name = $node[0][0];
		$capitalized_node_name = ucfirst($node_name);
		$rank_name = $ranks[$kingdom][$node[1]][0];
		$rank_id = $node[1];

		$show_children = $rank_id<$stop_rank && count($node[2])>0;

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

				$direct_children = [];
				$indirect_children = [];

				foreach($node[2] as $children_id){

					$children_rank_id = $tree[$children_id][1];

					if($ranks[$kingdom][$children_rank_id][1]==$rank_id)
						$direct_children[] = $children_id;
					else
						$indirect_children[] = $children_id;

				}

				if(count($indirect_children)!=0){
					$direct_children_rank = $ranks[$kingdom][$rank_id][2];
					//show_node([['(no ' . $ranks[$kingdom][$direct_children_rank][0] . ')'], $direct_children_rank, $indirect_children]);
					show_node([['incertae sedis'], $direct_children_rank, $indirect_children]);
				}

				foreach($direct_children as $children_id)
					show_node($tree[$children_id]); ?>

			</ul> <?php
		}

		echo '</li>';

	}

	show_node($tree[$kingdom]); ?>

</ul>

<section id="ranks">
<h3>Step 4: Select the taxonomic levels that are present in your database</h3><?php

$new_specify_ranks = [];
foreach($specify_ranks as $rank){

	$rank = explode(',',$rank);
	$new_specify_ranks[$rank[0]] = count($rank)!=1;

}
$specify_ranks = $new_specify_ranks;



foreach($ranks[$kingdom] as $rank_id => $rank){

	$rank = $rank[0];

	if(!array_key_exists($rank,$specify_ranks))
		continue;

	$checked = '';
	if($specify_ranks[$rank])
		$checked = ' checked'; ?>

	<div class="custom-control custom-checkbox">
		<input type="checkbox" class="custom-control-input rank" id="rank_<?=$rank_id?>" <?=$checked?>>
		<label class="custom-control-label" for="rank_<?=$rank_id?>"><?=$rank?></label>
	</div> <?php

} ?>

</section>
<section id="options">
<h3>Step 5: Select which optional data should be present</h3>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_1">
	<label class="custom-control-label" for="option_1">Include Common Names</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_2">
	<label class="custom-control-label" for="option_2">Include Authors</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_4" checked>
	<label class="custom-control-label" for="option_4">Split the resulting tree into CSV files of less than 7000 records</label>
</div>

<!--
<div class="custom-control custom-checkbox mb-4">
	<input type="checkbox" class="custom-control-input option" id="option_5" checked>
	<label class="custom-control-label" for="option_5">Exclude extinct taxa</label>
</div>
-->
</section>
<form action="<?=LINK?>generate_tree/?kingdom=<?=$kingdom?>" method="POST" class="mt-4">
	<input type="hidden" id="payload_field" name="payload">
	<button class="btn btn-success btn-lg" id="get_result_button" type="submit">Get results</button>
</form>
<script src="<?=LINK?>static/js/main<?=JS?>"></script>