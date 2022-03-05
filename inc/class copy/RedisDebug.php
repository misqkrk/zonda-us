<?
 class RedisDebug{
	public function __construct() {
		$this->redis = Connect::redis();
	}

	function log($in){
		  error_log(date("Y-m-d H:i:s").' || '.$in.PHP_EOL,3, '/var/www/bitbay/log/redis.log');
		  echo $in.PHP_EOL;
	}

	function get($key){
		$this->log('GET: '.$key);
		return $this->redis->get($key);
	}

	function set($key = null, $val = null, $time){
		$this->log('SET: '.$key);
		return $this->redis->set($key, $val, $time);
	}

	function getAllKeys($in= null){
		$this->log('getAllKeys: '.$in);
		return $this->redis->getAllKeys();
	}

	function delete($key= null){
		$this->log('delete: '.$key);
		return $this->redis->delete($key);
	}

	function del($key= null){
		$this->log('del: '.$key);
		return $this->redis->del($key);
	}	

	function exists($key= null){
		//$this->log('exists: '.$key);
		return $this->redis->exists($key);
	}	

	function publish($key = null, $val = null){
		$this->log('publish: '.$key);
		return $this->redis->publish($key, $val);
	}

	function keys($key = null){
		$this->log('keys: '.$key);
		return $this->redis->keys($key);
	}


}