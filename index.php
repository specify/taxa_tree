<?php

require_once('components/header.php');

if(!file_exists($tree_location))
	exit('Data is not compiled');

//kingdom > phylum > class > order
$tree = file_get_contents($tree_location);
$tree = json_decode($tree);
//genericName, scientificName, scientificNameAuthorship
function checkbox($class,$name,$collapsable=TRUE){

	if($collapsable)
		$collapse = '<button style="background-image: url('.LINK.'static/svg/arrow.svg)" class="arrow"></button>';
	else
		$collapse = '';

	return '<button class="checkbox"></button>
	'.$collapse. $name.'';

} ?>

<h1>Taxonomic tree generator</h1>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input" id="include_generic_names">
	<label class="custom-control-label" for="include_generic_names">Include Generic Names</label>
</div>

<div class="custom-control custom-checkbox">
	<input type="checkbox" class="custom-control-input" id="include_scientific_names">
	<label class="custom-control-label" for="include_scientific_names">Include Scientific Names</label>
</div>

<div class="custom-control custom-checkbox mb-4">
	<input type="checkbox" class="custom-control-input" id="include_authors">
	<label class="custom-control-label" for="include_authors">Include Authors</label>
</div>

<ul class="pl-0" id="root"> <?php

foreach($tree as $kingdom => $phylum_data){

	echo '<li data-name="'.$kingdom.'">'.checkbox('kingdom',ucfirst($kingdom)).'<ul class="collapsed">';
	foreach($phylum_data as $phylum => $class_data){

		echo '<li data-name="'.$phylum.'">'.checkbox('phylum',$phylum).'<ul class="collapsed">';
		foreach($class_data as $class => $order_data){

			echo '<li data-name="'.$class.'">'.checkbox('class',$class).'<ul class="collapsed">';

			foreach($order_data as $order)
				echo '<li data-name="'.$order.'">'.checkbox('order',$order,FALSE).'</li>';

			echo '</ul></li>';


		}
		echo '</ul></li>';

	}
	echo '</ul></li>';

} ?>

</ul>

<form action="<?=LINK?>generate_tree/" method="POST">
	<input type="hidden" id="payload_field" name="payload">
	<button class="btn btn-success btn-lg" id="get_result_button" type="submit">Get results</button>
</form>
<script src="<?=LINK?>static/js/main<?=JS?>"></script>