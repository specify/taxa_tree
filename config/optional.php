<?php

### DOWNLOADING ARCHIVES ###
# URL to get data from
$suffix = 'http://www.catalogueoflife.org/DCA_Export/zip/archive-kingdom-';

# List of kingdoms to download
$kingdoms = ['animalia','archaea','bacteria','chromista','fungi','plantae','protozoa','viruses'];

# File type
$prefix = '-bl2.zip';

# Final file type
$file_prefix = '.zip';



### UNZIPPING ARCHIVES ###

# Location for downloaded archives
$archives_path = WORKING_LOCATION . 'archives/';

# Location for the versions information
$versions_path = WORKING_LOCATION . 'versions.html';

# Versions page
$versions_page = 'http://www.catalogueoflife.org/DCA_Export/';

# Location for extracted data
$results_path = WORKING_LOCATION . 'extracted/';

# Folder path delimiter
$results_prefix = '/';



### COMPILING DATA ###

# Location of the taxa file inside of a downloaded zip archive
$taxonomy_path = 'taxa.txt';

# Location of compiled files
$compiled_path = WORKING_LOCATION . 'compiled/';

# Extension of compiled files
$compiled_prefix = '.json';

# Location of the complete preview tree
$tree_location = WORKING_LOCATION.'tree.json';

# Array of columns from the taxonomy_path file
$columns = array_flip(['taxonID','identifier','datasetID','datasetName','acceptedNameUsageID','parentNameUsageID','taxonomicStatus','taxonRank','verbatimTaxonRank','scientificName','kingdom','phylum','class','order','superfamily','family','genericName','genus','subgenus','specificEpithet','infraspecificEpithet','scientificNameAuthorship','source','namePublishedIn','nameAccordingTo','modified','description','taxonConceptID','scientificNameID','references','isExtinct']);



### STATS REPORTING ###

# A URL that would collect stats. See https://github.com/maxxxxxdlp/taxa_tree_stats
define('STATS_URL',LINK.'../stats/collect/');



### FOR DEBUG ONLY ###

# This will show success actions for most actions performed while the data refresh is running
define('VERBOSE',FALSE);

# Whether to output all PHP errors while DEVELOPMENT is set to FALSE
define('SHOW_ERRORS_IN_PRODUCTION',TRUE);