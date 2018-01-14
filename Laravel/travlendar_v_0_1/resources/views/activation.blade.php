<!DOCTYPE html>
<html>
	<head>
		<style type="text/css">
		.image-size {
		max-width: 50%;
		height: auto;
		width: auto\9
		}
		</style>
		<title>Account Activation</title>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css">
		<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js"></script>
	</head>
	<body>
		<center>
		<div class="container">
			<div style="height: 10px;"></div>
			<img class="image-size" src="http://168.235.96.252:8080/logo/logo.png" align="Middle">
			@if ($success == 1)
			<h2>Congratulations!<br><h2>
			<h2>Your account is now active.</h2>
			<h3>Head to the application and start planning!</h3>
			@else
			<h1>Mmmm! Something went wrong...</h1>
			@endif
		</div>
		</center>
	</body>
</html>