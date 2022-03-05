<?php
class Mongo{
	//getInsertedCount();
	//public $DB;
	//protected $data;

	public function __construct($DB = 'bb'){
		$this->DB = $DB;
		$this->client = new MongoDB\Client('mongodb://root:qXfCkY3GZxjJ@128.204.217.247:27017/bb?authSource=admin');
		$this->collection = $this->client->$DB;
		
		$this->redis = new Redis(); 
		$this->redis->connect('/var/run/redis/redis.sock');
		$this->redis->auth(REDIS_AUTH);
	}

	public function __get($name){
        //echo "Getting '$name'\n";
        $this->collection_table = $name;
        return $this;
	}

	public function __call($func = null, $arguments = null){
	 	$table = $this->collection_table;
	 	$arguments[0] ??= [];
	 	$arguments[1] ??= [];
	 	$arguments[2] ??= [];

		if(is_string($table)):
			try {
			   	return $this->tmp_results = $this->collection->$table->$func($arguments[0],$arguments[1], $arguments[2]);
			} catch (Exception $e) {
				if($e->getCode() == 11000):
					$this->log_error($e->getMessage());
				else:
					$this->log_error($e->getMessage(), true);
				endif;
			}
		else:
			return $this->tmp_results = $this->collection->$func($arguments[0],$arguments[1], $arguments[2]);
		endif;
	}


	// public function Array($null = null){
	
	// 	if(get_class($this->tmp_results) == 'MongoDB\Driver\Cursor'):
	// 		return $this->tmp_results->toArray();
	// 	else:
	// 		echo 'Call to undefined method toArray().'.PHP_EOL;
	// 		return [];
	// 	endif;

	// }

	public function log_error($in = '', $die = null){
		error_log($in, 0);
		if(isset($die)):
			die($in);
		else:
			echo $in.PHP_EOL;
		endif;
	}


	function findCache($query = [], $projection = [], $time = 5, $key_name = null){ // only array
		$t = microtime(true);
		if($time > 0):
			if(is_string($this->collection_table)):
				$tmp_prefix_table = $this->collection_table.':';
			else:
				$tmp_prefix_table = null;
			endif;


			if($key_name):
				$redis_key = 'Mongo:'.$this->DB.':'.$tmp_prefix_table.$key_name;
			else:
				$tmp_key = crc32(serialize([$query,$projection]));
				$redis_key = 'Mongo:'.$this->DB.':'.$tmp_prefix_table.$tmp_key;
			endif;
		endif;
		//echo $prefix;

		if($time > 0 && $results = igbinary_unserialize($this->redis->get($redis_key)) ): 
			$mode = 'redis';
		else:
			$mode = 'mongodb';
			$results = $this->find($query, $projection);
			if($results):
				$results = $results->toArray();
				$this->redis->set($redis_key, igbinary_serialize($results), $time);
			endif;
		endif;


		$this->timming = 'Query took: '.round((microtime(true)- $t),3).'ms. ['.$mode.']'; 
		return $results;

		//return $this->find($query, $projection)->toArray();
	}


}
?>