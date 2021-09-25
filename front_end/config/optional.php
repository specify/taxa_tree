<?php

### STATS REPORTING ###

# A URL that would collect stats. See https://github.com/specify/taxa_tree_stats
if(!defined('STATS_URL'))
  define('STATS_URL','http://biwebdbtest.nhm.ku.edu/sp7-stats/taxa_stats/collect/');



### FOR DEBUG ONLY ###

# Whether to output all PHP errors while DEVELOPMENT is set to FALSE
define('SHOW_ERRORS_IN_PRODUCTION',TRUE);