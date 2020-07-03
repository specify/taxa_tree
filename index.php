<?php

require_once('config/required.php');

?><!DOCTYPE html>
<html lang="en">
<head>

	<meta charset="utf-8">
	<title>Phylogenetic tree</title>
	<meta
			name="viewport"
			content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta
			name="author"
			content="Specify Software">
	<meta
			name="robots"
			content="index,follow">
	<link
			rel="icon"
			type="image/png"
			sizes="150x150"
			href="https://sp7demofish.specifycloud.org/static/img/fav_icon.png">
	<link
			rel="stylesheet"
			href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.0/css/bootstrap.min.css"
			integrity="sha256-aAr2Zpq8MZ+YA/D6JtRD3xtrwpEz2IqOS+pWD/7XKIw="
			crossorigin="anonymous"/>

</head>
<body class="mb-4">

<div class="container mt-5 mb-5">

	<h1>Phylogenetic tree generator</h1>

	<h2>Step 1</h2>
	<p>
		Got to
		<a href="https://itis.gov/servlet/" target="_blank">ITIS.GOV</a>
		website and conduct a search on taxonomic information.<br>
		You can also use <a href="https://itis.gov/advanced_search.html" target="_blank">advanced search tool</a>
	</p>

	<h2>Step 2</h2>
	<p>
		After finding necessary information, you will be redirected to a page that looks similar to
		<a href="https://itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=513023#null" target="_blank">this one</a>
		<br>Press the "Download DwC-A" button:<br>
		<img
				src="<?=LINK?>static/images/step_2.png"
				class="img-fluid"
				alt="Press the 'Download DwC-A' button at the top of the page">
	</p>

	<h2>Step 3</h2>
	<p>
		After you file is generated, you will be prompted to download either '.zip' or '.csv' file.
		Press on the name of that file to initialize the download process:<br>
		<img
				src="<?=LINK?>static/images/step_3.png"
				class="img-fluid"
				alt="Press on the name of that file to initialize the download process">

	</p>

	<h2>Step 4</h2>

	<form action="<?=LINK?>generate_tree/" method="POST" enctype="multipart/form-data">

		<div class="form-group">
			<label for="file">Import the resulting .zip or .csv file bellow and press 'Get results'</label>
			<input type="file" accept=".zip,.csv,.txt" class="form-control-file" name="file">
		</div>

		<button class="btn btn-success btn-lg" type="submit">Get results</button>

	</form><br>

	<h2>Step 5</h2>
	<p>
		You will get a CSV file that can be imported into Specify's Workbench tool.<br>
		Refer to documentation on how to do that.
	</p>

</div>