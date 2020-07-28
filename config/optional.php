<?php


$suffix = 'http://www.catalogueoflife.org/DCA_Export/zip/archive-kingdom-';
$kingdoms = ['animalia','archaea','bacteria','chromista','fungi','plantae','protozoa','viruses'];
$prefix = '-bl2.zip';
$file_prefix = '.zip';

$archives_path = WORKING_LOCATION . 'archives/';
$versions_path = WORKING_LOCATION . 'versions.html';
$versions_page = 'http://www.catalogueoflife.org/DCA_Export/';

$results_path = WORKING_LOCATION . 'extracted/';
$results_prefix = '/';

$taxonomy_path = 'taxa.txt';
$compiled_path = WORKING_LOCATION . 'compiled/';
$compiled_prefix = '.json';

$tree_location = WORKING_LOCATION.'tree.json';

$columns = array_flip(['taxonID','identifier','datasetID','datasetName','acceptedNameUsageID','parentNameUsageID','taxonomicStatus','taxonRank','verbatimTaxonRank','scientificName','kingdom','phylum','class','order','superfamily','family','genericName','genus','subgenus','specificEpithet','infraspecificEpithet','scientificNameAuthorship','source','namePublishedIn','nameAccordingTo','modified','description','taxonConceptID','scientificNameID','references','isExtinct']);


define('STATS_URL',LINK.'../stats/collect/');


### FOR DEBUG ONLY ###

# This will show success actions for most actions performed while the data refresh is running
define('VERBOSE',FALSE);

# Whether to output all PHP errors while DEVELOPMENT is set to FALSE
define('SHOW_ERRORS_IN_PRODUCTION',TRUE);