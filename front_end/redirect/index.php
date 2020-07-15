<?php

if(array_key_exists('tsn',$_GET))
	header('Location: https://itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value='.$_GET['tsn']);