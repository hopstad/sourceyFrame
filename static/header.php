<!DOCTYPE HTML>
<html>

	<head>
		<!-- Tittel -->
		<title><?php echo $GLOBALS['appName']; ?></title>
		<!-- Content og charset -->
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<!-- Favicon -->
		<link rel='shortcut icon' type='image/x-icon' href='./public/favicon/favicon.ico' />
		<?php if($GLOBALS['mobileFriendly']){ ?>
			<!-- MOBIL -->
			<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php } ?>
		<!-- Stylesheets -->
		<link rel="stylesheet" href="./public/css/style.main.css">
		<!-- JS -->
		<script src="./public/js/jquery-3.1.1.min.js" type="text/javascript"></script>
	</head>
	
	<body>
	
	<div class="reports">
	<?php if(isset($_GET['status'])){
		$status = $_GET['status'];
		if($status === 'sperret'){
			echo 'Brukeren er sperret i 10 min';
		}else if($status === 'loginFeilet' && !$Users->isUserLoggedIn()){
			echo 'Feil brukernavn og/eller passord';
		}else{
			echo 'Logget inn';
		}
		?>
		<script>
			$(document).ready(function(){
				$('.reports').show();
					setTimeout(function(){ 		
						$('.reports').fadeOut('slow');
					}, 2200);
				});
		</script>
		<?php } ?>
	</div>