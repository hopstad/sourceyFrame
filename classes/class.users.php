<?php

	class Users{
		
		private $user_name;
		private $user_email;
		private $user_group;
		private $user_isLoggedIn = false;
		private $db;
		private $reports;
		
		public function __construct($db){
			
			$this->db = $db;
			
			if(isset($_GET['logg_ut'])){
				$this->doLogOut();
			}else if(isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === 1){
				$this->loginWithSession();
			}else if(isset($_COOKIE['husk_meg'])){
				$this->loginWithCookie();
			}else if(isset($_POST['logg_inn'])){
				$this->loginWithPostData();
			}else if($GLOBALS['debug']){
				if(isset($_POST['createNewUserFromScratch'])){
					$this->createNewUserFromScratch();
				}
			}
			
		}
		
		# Slett Cookie
		private function deleteRememberMeCookie(){
			# Sjekker DB
			if (!empty($this->db)) {
				
				# Setter token til NULL i db
				if($this->db->query("UPDATE users SET remember_me_token = NULL WHERE id = :user_id", array("user_id"=>$_SESSION['user_id']))){
					# Fjerner token (setter tid til utløpt)
					setcookie('husk_meg', false, time() - (3600 * 3650));
					return true;
				}else{
					return false;
				}
			}
		}
		
		# Logg inn med Cookie
		private function loginWithCookie(){
			if(isset($_COOKIE['husk_meg'])){
				
				# Henter data fra Cookie
				list ($user_id, $token, $hash) = explode(':', $_COOKIE['husk_meg']);
				
				# Validerer HASH
				if ($hash == hash('sha256', $user_id . ':' . $token . COOKIE_SECRET_KEY) && !empty($token)) {
					if(!empty($this->db)){
						
						# Henter data fra DB
						$userData = $this->db->queryObj("SELECT * FROM users WHERE id = :id AND remember_me_token = :husk_meg_token AND remember_me_token IS NOT NULL", array("id"=>$user_id,"husk_meg_token"=>$token));

						if(isset($userData->id)){
							
												
							# Setter login SESSION og COOKIE							
							$_SESSION['user_id'] = $userData->id;
							$_SESSION['user_name'] = $userData->name;
							$_SESSION['user_email'] = $userData->email;
							$_SESSION['user_logged_in'] = 1;
							$_SESSION['user_group'] = $userData->group;
													
							# Setter lokale variabler
							$this->user_id = $userData->id;
							$this->user_name = $userData->name;
							$this->user_email = $userData->email;
							$this->user_group = $userData->group;
							$this->user_isLoggedIn = true;
							
							$this->newRememberMeCookie();
							return true;
							
						}else{	
							$this->doLogOut();
						}
		
					}
				}
			}else{
				$this->doLogOut();
			}
		}
		
		# Logg inn med Session
		private function loginWithSession(){
			
			$this->user_name = $_SESSION['user_name'];
			$this->user_email = $_SESSION['user_email'];
			$this->user_group = $_SESSION['user_group'];
			
			# Setter logged in til true
			$this->user_isLoggedIn = true;
			
		}
		
		# Logg inn via loginform
		private function loginWithPostData(){
			
			if(!isset($_POST['brukernavn']) || !isset($_POST['passord'])){
				return false;
			}
			
			// Henter POST data
			$username = trim($_POST['brukernavn']);
			$password = trim($_POST['passord']);
			if(isset($_POST['hasCheckbox'])){
				if(!isset($_POST['husk_meg'])){
					$rememberMe = false;
				}else{
					$rememberMe = true;
				}
			}else{
				$rememberMe = true;
			}
			
			// Sjekk om bruker finnes - Hent data
			if($userData = $this->getUserData($username)){
				
				// Sjekker antall login forsøk
				if($userData->login_attempts > 2){

					if($this->loginAttempts($username, 'sjekk') === true){
						
						if(!password_verify($password, $userData->password)){
							# +1 på loginforsøk
							$this->loginAttempts($username, 'opp');
							header("Location: /?status=feilPassord");
						}else{
							$this->doLogin($userData, $rememberMe);
						}
						
					}else{
						header("Location: /?status=sperret");
					}
					
				}else{
					
					if(!password_verify($password, $userData->password)){
						# +1 på loginforsøk
						$this->loginAttempts($username, 'opp');
						header("Location: /?status=feilPassord");
					}else{
						$this->doLogin($userData, $rememberMe);
					}
					
				}
				
			}else{
				header("Location: /?status=loginFeilet");
			}
			
		}
		
		# Logg inn
		private function doLogin($userData, $rememberMe){
			
			# Nullstiller loginattemps
			if($userData->login_attempts > 0){
				$this->loginAttempts($userData->username, 'nullstill');
			}
			
			# Setter login SESSION og COOKIE							
			$_SESSION['user_id'] = $userData->id;
			$_SESSION['user_name'] = $userData->name;
			$_SESSION['user_email'] = $userData->email;
			$_SESSION['user_logged_in'] = 1;
			$_SESSION['user_group'] = $userData->group;
									
			# Setter lokale variabler
			$this->user_id = $userData->id;
			$this->user_name = $userData->name;
			$this->user_email = $userData->email;
			$this->user_group = $userData->group;
			$this->user_isLoggedIn = true;
			
			if($rememberMe){
				$this->newRememberMeCookie();
			}
			
			# Redirecter
			$redirectUrl = '/';
			header("Location: $redirectUrl");
			
		}
		
		# Sjekk antall forsøk på login
		private function loginAttempts($user_name, $status){
			#@status opp, nullstill, sjekk
			
			$tid = date("U");
			$timer = '600'; # Tid i sekunder (10 min)
			
			# Øk forsøk med +1
			if($status === 'opp'){
				$this->db->query("UPDATE users SET login_attempts = login_attempts + 1, login_start = :tid_login_start
				WHERE username = :brukernavn",
				array("tid_login_start"=>$tid, "brukernavn"=>$user_name));
				
			# Nullstill
			}else if($status === 'nullstill'){
				$this->db->query("UPDATE users SET login_attempts = 0, login_start = 0
				WHERE username = :brukernavn",
				array("brukernavn"=>$user_name));
			# Sjekk tid siden sist
			}else if($status === 'sjekk'){
				$sjekkQry = $this->db->query("SELECT login_start FROM users WHERE username = :brukernavn",
				array("brukernavn"=>$user_name));
				foreach($sjekkQry as $sQ){
					
					$now = date("U") - 600;
					
					if($now > $sQ['login_start']){
						return true;
					}else{
						return false;
					}
				}
			}
		}
		
		private function newRememberMeCookie(){
			if (!empty($this->db)) {
				

				# Lager en 64 char random token string
				$random_token_string = hash('sha256', mt_rand());
				
				# ID
				$id = $_SESSION['user_id'];
				
				# Oppdaterer DB med ny token
				if($this->db->query("UPDATE users SET remember_me_token = :husk_meg_token WHERE id = :id", array("husk_meg_token"=>$random_token_string, "id"=>$id))){
					
					# Lag en Cookie string basert på User_id, random string og hash av begge
					$cookie_string_first_part = $_SESSION['user_id'] . ':' . $random_token_string;
					$cookie_string_hash = hash('sha256', $cookie_string_first_part . COOKIE_SECRET_KEY);
					$cookie_string = $cookie_string_first_part . ':' . $cookie_string_hash;
					
					# Sett Cookie
					setcookie('husk_meg', $cookie_string, time() + COOKIE_RUNTIME, NULL, NULL, NULL, TRUE);
					
					return true;
					
				}else{
					$this->errors[] = 'En feil med token generering i DB';
					$this->error();
				}
			}
		}
		
		# Henter brukerdata
		private function getUserData($username){
			if(!empty($this->db)){
				if($userDataQry = $this->db->queryObj("SELECT * FROM users WHERE username = :username", array("username"=>$username))){
					return $userDataQry;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		
		# Logg ut
		private function doLogOut(){
			setcookie('husk_meg', false, time() - (3600 * 3650));
			$_SESSION = array();
			session_destroy();
			$this->user_isLoggedIn = false;
		}
		
		# Sjekk om User er logget inn
		public function isUserLoggedIn(){
			return $this->user_isLoggedIn;
		}
		
		# Opprett ny bruker (Kun ved debug)
		private function createNewUserFromScratch(){
			
			$username = trim($_POST['brukernavn']);
			$password = trim($_POST['passord']);
			
			$hash_cost_factor = (defined('HASH_COST_FACTOR') ? HASH_COST_FACTOR : null);
			$user_password_hash = password_hash($password, PASSWORD_DEFAULT, array('cost' => $hash_cost_factor));
			
			if($this->db->query("INSERT INTO users
			(username, password)
			VALUES(:username, :password)",
			array("username"=>$username, "password"=>$user_password_hash))){
				echo 'BRUKER OPPRETTET';
			}else{
				echo 'FEILET';
			}
			
		}
		
		// PUBLIC FUNCTIONS
		public function getUserName(){
			return $this->user_name;
		}
		public function getUserEmail(){
			return $this->user_email;
		}
		public function getUserGroup(){
			return $this->user_group;
		}
		
	}

?>