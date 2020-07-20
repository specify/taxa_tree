<?php


if(strpos($_SERVER['HTTP_HOST'],'localhost')!==FALSE){
	define('DEVELOPMENT',TRUE);
	define('CONFIGURATION','localhost');
}
elseif($_SERVER['HTTP_HOST']=='specify.maxxxxxdlp.ml'){
	define('DEVELOPMENT',TRUE);
	define('CONFIGURATION','ec2');
}
else {
	define('DEVELOPMENT',FALSE);
	define('CONFIGURATION','production');
}


if(CONFIGURATION==='localhost'){

	# Address the website would be served on
	define('LINK', 'http://localhost:80/');

	# Set this to an empty folder
	# Make sure the web server has write permissions to this folder
	# **Warning!** All of the files present in this directory would be deleted
	define('WORKING_LOCATION','/Users/mambo/Downloads/python-taxonomy/');

}

elseif(CONFIGURATION==='ec2'){

	define('LINK', 'https://specify.maxxxxxdlp.ml/taxa_itis/front_end/');

	define('WORKING_LOCATION','/home/ec2-user/data/python-taxonomy/');

}
else { # these settings would be used during production

	define('LINK', 'http://biwebdbtest.nhm.ku.edu/taxa/');

	define('WORKING_LOCATION','/home/sp7-stats/python-taxonomy/');

}