<?php


if(strpos($_SERVER['HTTP_HOST'],'localhost')!==FALSE){
	define('DEVELOPMENT',TRUE);
	define('CONFIGURATION','localhost');
}
elseif($_SERVER['HTTP_HOST']=='specify.maxxxxxdlp.ml'){
	define('DEVELOPMENT',TRUE);
	define('CONFIGURATION','ec2');
}
elseif($_SERVER['SERVER_ADDR']=='129.237.201.1'){
	define('DEVELOPMENT',FALSE);
	define('CONFIGURATION','production');
}
else
	exit('Modify settings in /config/required.php');


if(CONFIGURATION==='localhost'){

	# Address the website would be served on
	define('LINK', 'http://localhost:80/');

	# Set this to an empty folder
	# Make sure the web server has write permissions to this folder
	# **Warning!** All of the files present in this directory would be deleted
	define('WORKING_LOCATION','/Users/maxpatiiuk/Downloads/sites_data/col/');

}

elseif(CONFIGURATION==='ec2'){

	define('LINK', 'https://specify.maxxxxxdlp.ml/taxa_itis/front_end/');

	define('WORKING_LOCATION','/home/ec2-user/data/col/');

}
elseif(CONFIGURATION==='production') { # these settings would be used in production

	define('LINK', 'https://taxon.specifysoftware.org/col/');

	define('WORKING_LOCATION','/usr/share/nginx/data/col/');

}