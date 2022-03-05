<?
class Connect{

	static function DB($connect = SQL_1){
		return $DB = new DBPDO($connect);
	}
	
	static function redis($db = false){
		$redis = new Redis();
		$redis->connect('redis-1', '6379');
		$redis->auth(REDIS_AUTH);
		
		if ($db):
			$redis->select($db);
		endif;

		return $redis;
	}

	static function API($bb_connect = BB_API){
		return $API = new API($bb_connect);
	}

}