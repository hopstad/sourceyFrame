<?php

	/**
	 * SourceyLoader
	 *
	 * @ Versjon 1.0
	 * @ Morten Hopstad
	 */

	class sourceyLoader{
		
		# Variabler som endres etter ønske
		const debugMode = true; // Må endres til FALSE i Production!
		const sslMode = false; // Bruk SSL?
		const appName = 'SourceyFrame'; // Navn på nettside
		const domainName = 'SourceyFrame'; // Domenenavn
		const appAdress = 'sourceyframe.co.tdc.no'; // Adresse til nettside
		const requireLogin = true; // Må man logge inn for å vise sidene?
		const mobileFriendly = true; // Mobilstøtte?
		
		# Init
		public static function run(){
			
			self::setSession(static::domainName, 0, '/', '', static::sslMode);
			self::setDefines();
			self::setDebug();
			self::setGlobals();
			self::setLocales();
			self::setAutoLoader();
			
		}
		
		# Setter Defined variabler
		private static function setDefines(){
			
			define("ROOT", getcwd() . '/');
			define("INCLUDES", ROOT . "../includes/");
			define("PUBLICS", ROOT . "public" . '/');
			define("STATICS", ROOT . "../static/");
			define("CONFIG", ROOT. "../config/");
			define("LOGS", ROOT . "../logging/");
			define("CLASSES", ROOT . "../classes/");
			define("CORE", ROOT . "../core/");
			define("DEBUG", static::debugMode);
			define("COOKIE_RUNTIME", 1209600);
			
			$ini = parse_ini_file(CONFIG . "config.ini.php", true);
			
			define("HASH_COST_FACTOR", $ini['HASH_FACTORS']['cost_factor']);
			define("COOKIE_SECRET_KEY", $ini['HASH_FACTORS']['cookie_secret_key']);
			
		}
		
		# Vis errors og warning eller putt dem i logg
		private static function setDebug(){

			if(DEBUG){
				ini_set('display_errors', 1);
				ini_set('display_startup_errors', 1);
				error_reporting(E_ALL);
			}else{
				ini_set("log_errors", 1);
				ini_set('error_log', LOGS . 'log.php.txt'); 
			}
			
		}
		
		# Setter Secure Session
		private static function setSession($name, $limit = 0, $path = '/', $domain = null, $secure = null){
			
			ini_set('session.cookie_httponly', 1);
			ini_set('session.use_only_cookies', 1);
			ini_set('session.cookie_secure', static::sslMode);
			
			session_name($name . '_Session');

			$domain = isset($domain) ? $domain : isset($_SERVER['SERVER_NAME']);

			$https = isset($secure) ? $secure : isset($_SERVER['HTTPS']);

			session_set_cookie_params($limit, $path, $domain, $secure, true);
			session_start();
			  
			if(self::validateSession()){
				if(!self::preventHijacking()){
					$_SESSION = array();
					$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
					$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
					self::regenerateSession();

				}elseif(rand(1, 100) <= 5){
					self::regenerateSession();
				}
			}else{
				$_SESSION = array();
				session_destroy();
				session_start();
			}

		}
		
		# Hindrer Session Hijacking
		private static function preventHijacking(){
			if(!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent']))
				return false;

			if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
				return false;

			if( $_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
				return false;

			return true;
		}
		
		# Lag ny Session variabel (5% sjanse per refresh)
		private static function regenerateSession(){

			if(isset($_SESSION['OBSOLETE']) || $_SESSION['OBSOLETE'] == true)
				return;

			$_SESSION['OBSOLETE'] = true;
			$_SESSION['EXPIRES'] = time() + 10;

			session_regenerate_id(false);

			$newSession = session_id();
			session_write_close();

			session_id($newSession);
			session_start();

			unset($_SESSION['OBSOLETE']);
			unset($_SESSION['EXPIRES']);
		}
		
		# Valider Session
		private static function validateSession(){
			if( isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']) )
				return false;

			if(isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time())
				return false;

			return true;
		}
		
		# Setter GLOBAL variabel
		private static function setGlobals(){
			
			$GLOBALS = array(
				'appName' => static::appName,
				'debug' => static::debugMode,
				'requireLogin' => static::requireLogin,
				'mobileFriendly' => static::mobileFriendly,
				'months' => array(
					'Januar',
					'Februar',
					'Mars',
					'April',
					'Mai',
					'Juno',
					'Juli',
					'August',
					'September',
					'Oktober',
					'November',
					'Desember'
				),
				'days' => array(
					'Mandag', 
					'Tirsdag', 
					'Onsdag', 
					'Torsdag', 
					'Fredag', 
					'Lørdag', 
					'Søndag'
				),
			);
			
		}
		
		# Setter lokale variabler. Tidssone og navn på måneder osv
		private static function setLocales(){
			
			date_default_timezone_set('Europe/Oslo'); 
			setlocale(LC_TIME, 'nb_NO');
			
		}
		
		# Initierer alle Classes
		public static function setAutoLoader(){
			require_once CORE . 'autoLoader.php';
			spl_autoload_register('Autoloader::loader');
		}
		
		
	}


?>