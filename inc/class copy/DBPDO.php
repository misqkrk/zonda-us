<?php
class DBPDO {
	// ATTR_EMULATE_PREPARES php7.3-mysqlnd

	public $pdo;
	public $error;
	public $status_error;
	protected $redis;

	function __construct($databse = SQL_1) {
		$this->database = $databse;

		$this->redis = new Redis(); 
		$this->redis->connect('/var/run/redis/redis.sock');
		$this->redis->auth(REDIS_AUTH);
		$this->connect();
	}

	function __destruct() {
		//$this->close();
	}

	function prep_query($query){
		return $this->pdo->prepare($query);
	}

	function connect(){
		if(!$this->pdo):
			$dsn      = 'mysql:dbname=' . $this->database['name'] . ';host=' . $this->database['host'];
			$user     = $this->database['user'];
			$password = $this->database['pass'];

			try {
				$this->pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_EMULATE_PREPARES => false));
				$this->pdo->exec("set names utf8");
				return true;
			} catch (PDOException $e) {
				$this->error = $e->getMessage();
				die($this->error);
				return false;
			}
		else:
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			return true;
		endif;
	}

	function table_exists($table_name){
		$stmt = $this->prep_query('SHOW TABLES LIKE ?');
		$stmt->execute([$table_name]);
		return $stmt->rowCount() > 0;
	}

	function execute($query, $values = null){
		if(!$this->status()):
			$this->pdo = null;
			$this->connect();
		endif;
		
		//echo $query.PHP_EOL;
		if($values == null): $values = [];
		elseif(!is_array($values)): $values = [$values];
		endif;
//print_r($values);
		$stmt = $this->prep_query($query);
		// dorobić error log class ! i zapisywąć błedne zapytania do pliku
		//if(!$stmt):
			//return 0;
		//else:
			$st = $stmt->execute($values);
		//endif;

		if(!$st) $this->status_error = true;
		return $stmt;
	}

	function fetch($query, $values = null, $key = null, $MemTime = null, $MemKey = null){
		//tutaj key nie używany
		if($MemTime > 0):
			if(!isset($MemKey)):
				$MemKey = crc32($query);
				$is_select = (strtolower( explode(' ', $query)[0] ) === 'select' ? true : false);
				$prefix = 'SQL:TMP:';
			else:
				$is_select = true;
				$prefix = 'SQL:KEY:';
			endif;
		endif;

		if($is_select && $MemTime > 0 && $results = igbinary_unserialize($this->redis->get($prefix.$MemKey)) ): return $results;
		else:
		//error_log(date("Y-m-d H:i:s").' || '.$query.PHP_EOL.'------------------------------------------------'.PHP_EOL,3, '/var/www/log/mysql.log');

			if($values == null): $values = [];
			elseif(!is_array($values)): $values = [$values];
			endif;

			$stmt = $this->execute($query, $values);
			$results =  $stmt->fetch(PDO::FETCH_ASSOC);

			if($MemTime > 0 && $is_select) $this->redis->set($prefix.$MemKey, igbinary_serialize($results), $MemTime);
  			return $results;	
		endif;
	}

	function fetchAll($query, $values = null, $key = null, $MemTime = null, $MemKey = null){

		//echo '##########################################'.$query.PHP_EOL;
			//$this->redis->set('debug:'.crc32($query), serialize($query), 90 );

		if($MemTime > 0):
			if(!isset($MemKey)):
				$MemKey = crc32($query);
				$is_select = (strtolower( explode(' ', $query)[0] ) === 'select' ? true : false);
				$prefix = 'SQL:TMP:';
			else:
				$is_select = true;
				$prefix = 'SQL:KEY:';
			endif;
		endif;

		if($is_select && $MemTime > 0 && $results = igbinary_unserialize($this->redis->get($prefix.$MemKey)) ): 
			return $results;
		else:
		//error_log(date("Y-m-d H:i:s").' || '.$query.PHP_EOL,3, '/var/www/log/mysql.log');

			if($values == null): $values = [];
			elseif(!is_array($values)): $values = [$values];
			endif;

			$stmt = $this->execute($query, $values);
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if($key != null && $results[0][$key]):
				$keyed_results = [];
				foreach($results as $result):
					$keyed_results[$result[$key]] = $result;
				endforeach;
				$results = $keyed_results;
			endif;
		
			if($MemTime > 0 && $is_select) $this->redis->set($prefix.$MemKey, igbinary_serialize($results), $MemTime);

  			return $results;
		endif;
	}

	function lastInsertId(){
		return $this->pdo->lastInsertId();
	}

	function rowCount(){ 
		return $this->pdo->rowCount();
	}

	function errorInfo(){ 
		return $this->pdo->errorInfo();
	}

	function close(){
		try{
			$ConnID = $this->fetch('SELECT CONNECTION_ID() AS c');
         $ConnID = $ConnID['c'];
         exec('mysqladmin -u '.DATABASE_USER.' -p'.DATABASE_PASS.' kill ' . $ConnID);
         $this->pdo = null; //Closes connection
      }catch (PDOException $e){
      	die($e->getMessage());
      }
	}
	function connID(){
		$ConnID = $this->fetch('SELECT CONNECTION_ID() AS c');
      return $ConnID['c'];
	}

	function fetch_status($query, $values = null){
		$stmt = $this->prep_query($query);
		$st = $stmt->execute([]);
		$results =  $stmt->fetch(PDO::FETCH_ASSOC);
  		return $results;	
	}

	function status(){
		$ConnID = $this->fetch_status('SELECT CONNECTION_ID() AS c');
      return $ConnID['c'];
	}




	public function insertOne($table, $values = [], $insertType = 'INSERT INTO'){
		if(!$this->status()):
			$this->pdo = null;
			$this->connect();
		endif;
		
		$query = $insertType.' `'.$table.'` '.$this->get_key($values).' VALUES '.$this->get_values($values).';';
		//print_r($query);

		$stmt = $this->prep_query($query);
		//$st = $stmt->execute([]);


		try {
		    $st = $stmt->execute([]);
		} catch (Exception $e) {
		    $text_line = '------------------------------------------------------------------------------------------------'.PHP_EOL;
		    $text_err = $e->getMessage().PHP_EOL;
			 $text_err .= 'TABLE: '.$table.PHP_EOL;
			 $text_err .= 'VALUES: '.print_r($values,true).PHP_EOL;

		    error_log(Time::timeMs().PHP_EOL.$text_err.$text_line, 3, DIR_LOG."sql/sql-".date("d-m-Y").".log");
		    echo 'Caught exception: ',  $e->getMessage().PHP_EOL;
		}


		return $this->pdo->lastInsertId();
	}

	public function insertMulti($table, $values = [], $insertType = 'INSERT INTO'){
		$first_key = array_key_first($values);
		foreach ($values as $v):
			$val[] = $this->get_values($v);
		endforeach;
		
		if(is_array($val)):
			$valuesMulti = implode(', ', $val);
			$query = $insertType.' `'.$table.'` '.$this->get_key($values[$first_key]).' VALUES '.$valuesMulti.';';
		//print_r($query);

			$stmt = $this->prep_query($query);
			//$st = $stmt->execute([]);

			try {
			    $st = $stmt->execute([]);
			} catch (Exception $e) {
			    $text_line = '------------------------------------------------------------------------------------------------'.PHP_EOL;
			    $text_err = $e->getMessage().PHP_EOL;
				 $text_err .= 'TABLE: '.$table.PHP_EOL;
				 $text_err .= 'VALUES: '.print_r($values,true).PHP_EOL;

			    error_log(Time::timeMs().PHP_EOL.$text_err.$text_line, 3, DIR_LOG."sql/sql-".date("d-m-Y").".log");
			    echo 'Caught exception: ',  $e->getMessage().PHP_EOL;
			}

			return $this->pdo->lastInsertId();
		else:
			return null;
		endif;
	}

	public function insertOrUpdate($table, $values, $insertType = 'INSERT INTO' ){
		if(!$this->status()):
			$this->pdo = null;
			$this->connect();
		endif;

		$query = $insertType.' `'.$table.'` '.$this->get_key($values).' VALUES '.$this->get_values($values).' ON DUPLICATE KEY UPDATE '.$this->get_values_update($values).';';
		//print_r($query);
		$stmt = $this->prep_query($query);
		$st = $stmt->execute([]);
		return $this->pdo->lastInsertId();
	}

public function v($v){
	if(is_string($v)):
		if( !isset($v) || empty($v)):
			$out = 'NULL';
		elseif(str_starts_with($v, '++')):
			$out = substr($v, 2);
		else:
			$out = '\''.$v.'\'';
		endif;
	elseif(is_int($v)):
		$out = '\''.$v.'\'';
	elseif($v === false): // false
		$out = '\'0\'';
	elseif($v === true): // false
		$out = '\'1\'';
	elseif(is_null($v)): // false
		$out = 'NULL';
	else:
		$out = '\''.$v.'\'';
	endif;
	return $out;	
}

	function get_values($in){
		if($in):
			foreach ($in as $k => $v):
				if(is_string($v)):
					if( !isset($v) || empty($v)):
						$out[] = 'NULL';
					elseif(str_starts_with($v, '++')):
						$out[] = substr($v, 2);
					else:
						$out[] = '\''.$v.'\'';
					endif;
				elseif(is_int($v)):
					$out[] = '\''.$v.'\'';
				elseif($v === false): // false
					$out[] = '\'0\'';
				elseif($v === true): // false
					$out[] = '\'1\'';
				elseif(is_null($v)): // false
					$out[] = 'NULL';
				else:
					$out[] = '\''.$v.'\'';
				endif;
			endforeach;
			return '('.implode(', ', $out).')';
		else:
			return null;
		endif;
	}

	function get_values_update($in){
		if($in):
			foreach ($in as $k => $v):
				if(is_string($v)):
					if( !isset($v) || empty($v)):
						$out[] = '`'.$k.'` = NULL';
					elseif(str_starts_with($v, '++')):
						//$out[] = '`'.$k.'` = \''.substr($v, 2).'\'';
						$out[] = '`'.$k.'` = '.substr($v, 2);

					else:
						$out[] = '`'.$k.'` = \''.$v.'\'';
					endif;
				elseif(is_int($v)):
					$out[] = '`'.$k.'` = \''.$v.'\'';
				elseif($v === false): // false
					$out[] = '`'.$k.'` = \'0\'';
				elseif($v === true): // false
					$out[] = '`'.$k.'` = \'1\'';
				elseif(is_null($v)): // false
					$out[] = '`'.$k.'` = NULL';
				else:
					$out[] = '`'.$k.'` = \''.$v.'\'';
				endif;
			endforeach;
			return implode(', ', $out);
		else:
			return null;
		endif;
	}


	function get_key($in){
		if($in):
			foreach ($in as $k => $v):
				$out[] = '`'.$k.'`';
			endforeach;
			return '('.implode(', ', $out).')';
		else:
			return null;
		endif;
	}


}