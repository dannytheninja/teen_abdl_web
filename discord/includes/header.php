<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $title ?? "(No title set)"; ?></title>
		<link rel="stylesheet" type="text/css" href="../libs/bootstrap/css/bootstrap.min.css" />
		<script src="../libs/jquery/jquery-3.3.1.min.js"></script>
		<script src="../libs/popper/popper-1.14.3.min.js"></script>
		<script src="../libs/bootstrap/js/bootstrap.min.js"></script>
		
		<script src="https://www.google.com/recaptcha/api.js"></script>
		
		<link rel="stylesheet"
			href="https://use.fontawesome.com/releases/v5.7.1/css/all.css"
			integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
			crossorigin="anonymous" />
	</head>
	<body>
		<div class="container">
			<div class="jumbotron">
				<h1 class="display-4">/r/<?php echo REDDIT_SUB_NAME; ?> Discord</h1>
			</div>
		</div>
	