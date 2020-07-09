<?php

require_once('components/header.php');
head();

if(!file_exists($tree_location))
	exit('Data is not compiled');

//kingdom > phylum > class > order
$tree = file_get_contents($tree_location);
$tree = json_decode($tree,TRUE);
//genericName, scientificName, scientificNameAuthorship


//Show the tree and other options
$arrow_location = LINK.'static/svg/arrow.svg';
function checkbox($name,$collapsable=TRUE){
	global $arrow_location;

	if($collapsable)
		$collapse = '<button style="background-image: url('.$arrow_location.')" class="arrow"></button>';
	else
		$collapse = '';

	return '<button class="checkbox"></button>
	'.$collapse. $name.'';

} ?>

<h1>Taxonomic tree generator</h1>

<h3>Step 1: Select the nodes you want to have in your database</h3>

<ul class="pl-0" id="root"> <?php

	$levels_to_show = 4;

	function show_node($node_name,$node,$level=0){

		global $levels_to_show;

		$show_children = $level<$levels_to_show;

		echo '<li data-name="'.$node_name.'">'.checkbox(ucfirst($node_name),$show_children);

		if($show_children){ ?>
			<ul class="collapsed"> <?php

				foreach($node as $node_name => $node_data)
					show_node($node_name,$node_data,$level+1); ?>

			</ul> <?php
		}

	}

	foreach($tree as $node_name => $node_data)
		show_node($node_name,$node_data); ?>

</ul>

<h3>Step 2: Select which optional data should be present</h3>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_1">
	<label class="custom-control-label" for="option_1">Include Common Names</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_2">
	<label class="custom-control-label" for="option_2">Include Authors</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input option" id="option_4">
	<label class="custom-control-label" for="option_4">Add links to <a href="http://catalogueoflife.org/">catalogueoflife.org</a></label>
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