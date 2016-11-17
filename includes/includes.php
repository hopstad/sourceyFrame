<?php
	// Inkluderer sider
	include STATICS . 'header.php';
	
	if($GLOBALS['requireLogin']){
				
		if($Users->isUserLoggedIn()){
			
			if(isset($_GET['include'])){
				
				$inc = $_GET['include'];
				
				if(!$this->checkInclude()){
					include INCLUDES . '404.php';
				}else{
					include INCLUDES . $inc . '.php';
				}
				
			}else{
				include INCLUDES . 'forside.php';
			}
			
		}else{
			include INCLUDES . 'login.php';
		}
				
	}else{
		
		if(isset($_GET['include'])){
				
			$inc = $_GET['include'];
				
			if(!$this->checkInclude()){
				include INCLUDES . '404.php';
			}else{
				include INCLUDES . $inc . '.php';
			}
				
		}else{
			include INCLUDES . 'forside.php';
		}
		
	}
	
	include STATICS . 'footer.php';
	
?>