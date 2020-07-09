<?php

if(array_key_exists('id',$_GET))
	header('Location: http://www.catalogueoflife.org/col/browse/tree/id/'.$_GET['id']);