<?php

if(!array_key_exists('file',$_FILES)){ ?>

	<form method="post" enctype="multipart/form-data">
		<input type="file" name="file" accept="*.zip"><br>
		<input type="submit" value="Make this file work with SpWizard!">
	</form> <?php

	exit;

}

define('COL',"\t");
define('LINE',"\n");

header("Pragma: no-cache");
header("Expires: 0");
header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=".$_FILES["file"]["name"]);


$handle = FALSE;
if(array_key_exists('raw',$_FILES['file'])){
  $_FILES['file']['raw'] = explode("\n",$_FILES['file']['raw']);
  function get_line(){
    static $index = -1;
    $index++;
    if(count($_FILES['file']['raw'])<=$index)
      return FALSE;
    else
      return $_FILES['file']['raw'][$index];
  }
}
else {
  $handle = fopen($_FILES["file"]["tmp_name"], "r");
  if(!$handle)
    die;
  function get_line(){
    global $handle;
    return fgets($handle);
  }
}


$columns = get_line();
$columns = str_replace("GUID","LSID",$columns);
$columns = explode("\t",$columns);
$columns = array_flip($columns);

if(array_key_exists('Division',$columns)){
	$columns['Phylum'] = $columns['Division'];
	unset($columns['Division']);
}

$results_array = [
	'Kingdom',
//	'Kingdom Author',
//	'Kingdom Common Name',
//	'Kingdom Source',
	'Phylum',
//	'Phylum Author',
//	'Phylum Common Name',
//	'Phylum Source',
	'Class',
//	'Class Author',
//	'Class Common Name',
//	'Class Source',
	'Order',
//	'Order Author',
//	'Order Common Name',
//	'Order Source',
	'Family',
//	'Family Author',
//	'Family Source',
	'Genus',
//	'Genus Author',
//	'Genus Common Name',
//	'Genus Source',
	'Species',
	'Subspecies',
	'Family Common Name',
	'Species Author',
	'Species Source',
//	'Species LSID',
	'Species Common Name',
	'Subspecies Author',
	'Subspecies Source',
//	'Subspecies LSID',
	'Subspecies Common Name',
];

$results = implode("\t",$results_array);
$header = strtolower($results);
echo $header.LINE;

$results_array = explode("\t",$results);

while (($line = get_line()) !== false) {

	$line = trim($line);

	$line = explode("\t",$line);

	$result_line = '';
	foreach($results_array as $result){

		if($result_line!='')
			$result_line .= COL;

		if(array_key_exists($result,$columns) && array_key_exists($columns[$result],$line))
			$result_line .= $line[$columns[$result]];

	}

	$result_line .= LINE;

	echo $result_line;

}

if($handle)
  fclose($handle);