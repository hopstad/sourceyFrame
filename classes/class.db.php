<?php

	class DB{
		
		private $db_pdo;
		private $db_host;
		private $db_dbname;
		private $db_username;
		private $db_password;
		private $db_settings;
		private $db_connected;
		private $db_errorLogs;
		private $db_parameters;
		private $db_sQuery;
		
		public function __construct(){
			
			$this->setCredentials();
			$this->connect();
			
		}
		
		# Setter DB login info
		private function setCredentials(){
			
			$this->db_settings = parse_ini_file(CONFIG . "config.ini.php", true);
			
			$this->db_host = $this->db_settings['DB']['host'];
			$this->db_dbname = $this->db_settings['DB']['database'];
			$this->db_username = $this->db_settings['DB']['dbuser'];
			$this->db_password = $this->db_settings['DB']['dbpassword'];
			
		}
		
		# Koble til DB
		private function connect(){
			
			$dsn = 'mysql:dbname='.$this->db_dbname.';host='.$this->db_host.'';
			
			try{
				
				$this->db_pdo = new PDO($dsn, $this->db_username, $this->db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET utf8"));
				$this->db_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->db_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$this->db_connected = true;
				
			}catch (PDOException $e) {
				$this->ExceptionLog($e->getMessage());
				die();
			}
			
		}
		
		# Vanlig Query
		public function query($query,$params = null, $fetchmode = PDO::FETCH_ASSOC){

			$query = trim($query);
			$this->init($query,$params);
			$rawStatement = explode(" ", $query);
			
			# Which SQL statement is used 
			$statement = strtolower($rawStatement[0]);
			
			if ($statement === 'select' || $statement === 'show') {
				return $this->db_sQuery->fetchAll($fetchmode);
			}
			elseif ( $statement === 'insert' ||  $statement === 'update' || $statement === 'delete' ) {
				return $this->db_sQuery->rowCount();	
			}	
			else {
				return NULL;
			}

		}
		
		# Query Object
		public function queryObj($query, $params = null){
			
			$query = trim($query);

			# Prepare query
			$obj = $this->db_pdo->query($query);
			if(!empty($params)){
				foreach($params as $key => $value){
					$par = ':' . $key;
					$obj->bindValue($par, $value, PDO::PARAM_STR);
				}
			}
				
			$obj->execute();
			return $obj->fetchObject();
				
		}
		
		# Init
		private function init($query,$parameters = ""){
			
			if(!$this->db_connected) { $this->Connect(); }
			
			try {
				# Prepare query
				$this->db_sQuery = $this->db_pdo->prepare($query);
				
				# Add parameters to the parameter array	
				$this->bindMore($parameters);
				# Bind parameters
				if(!empty($this->db_parameters)) {
					foreach($this->db_parameters as $param)
					{
						$parameters = explode("\x7F",$param);
						$parameters[0] = trim($parameters[0]);
						$parameters[1] = trim($parameters[1]);
						$this->db_sQuery->bindParam($parameters[0],$parameters[1]);
					}		
				}
				$this->succes = $this->db_sQuery->execute();		
			}catch(PDOException $e){
				$this->ExceptionLog($e->getMessage(), $query );
				die();
			}

			$this->db_parameters = array();
			
		}
		
		# Bind
		private function bind($para, $value){	
			$this->db_parameters[sizeof($this->db_parameters)] = ":" . $para . "\x7F" . $value;
		}
		
		# Bind flere
		private function bindMore($parray){
			if(empty($this->db_parameters) && is_array($parray)) {
				$columns = array_keys($parray);
				foreach($columns as $i => &$column)	{
					$this->bind($column, $parray[$column]);
				}
			}
		}
		
		# Logg errors til fil - Eller vis hvis debugmode
		private function ExceptionLog($message , $sql = ""){
			
			$exception  = '<br><br><br><br>Unhandled Exception.' . date("d.m.y H:i") . ' <br />';
			$exception .= $message;
			$exception .= "<br /><br>En feil har skjedd.";
			
			if(!empty($sql)) {
				$message .= "\r\nRaw SQL : "  . $sql;
			}
			
			if($GLOBALS['debug'] === true){
				
				echo $exception;	
				
			}else{
				
				$date = date("d.m.y H:i");
				$fullErrorString = $date . ' : ' . $message;
				
				$this->db_errorLogs = fopen(LOGS . "log.db.txt", "w");
				
				fwrite($this->db_errorLogs, $fullErrorString);
				fclose($this->db_errorLogs);
				$this->db_errorLogs = NULL;
				
				$this->errorReport('104');
				return false;
			}
			
		}
		
	}

?>