<?
class Time {
	static function timeMs($in = null, $date = null){
	    if($in):
	        return date("Y-m-d H:i:s", substr($in,0,-3)).'.'.substr($in,-3);
	    elseif($date):
	        return date("Y-m-d H:i:s");
	    else:
	        $m = microtime(true);
	        $e = explode('.',$m);
	        return date("Y-m-d H:i:s.", $e[0]).substr($e[1],0,3);
	    endif;
	}

	static function onlyTime($in = null, $date = null){
	    if($in):
	        return date("H:i:s", substr($in,0,-3));

	    endif;
	}

	static function time_to_unixtimestamp($date = 'now'){
		if(!$date): return strtotime('now');
		else: return strtotime($date);
		endif;
	}

	static function timestamp_to_date($timestamp = null){
		if(!$timestamp):
			return date('Y-m-d H:i:s');
		else:
			return date('Y-m-d H:i:s', $timestamp);
		endif;
	}

	static function is_weekend($date = 'now'){
		$d = date('N', Time::time_to_unixtimestamp($date) );
		if($d == 6 || $d == 7):
			return true;
		else:
			return false;
		endif;
	}

	static function seconds_to_min($s){
		if($s < 60):
			return $s.'s';
		elseif($s < 600):
			$m = intval($s/60);
			$s = $s-($m*60);
			return $m.'m '.$s.'s';
		elseif($s < 3600):
			$m = intval($s/60);
			return $m.'m ';
		elseif($s < 86400):
			$h = intval($s/3600);
			$s = $s-($h*3600);
			$m = intval($s/60);
			return $h.'h '.$m.'m';
		else:
			$d = intval($s/86400);
			$s = $s-($d*86400);
			$m = intval($s/60);
			$h = intval($m/60);
			return $d.'d '.$h.'h';
		endif;
	}

	static function slaap($seconds) { 
	    $seconds = abs($seconds); 
	    if($seconds < 1): 
	       usleep($seconds * 1000000); 
	    else: 
	       sleep($seconds); 
	    endif;    
	}	




	static function timeToMongo($in = null){
		$test = new DateTime($in); // NOW / 2020-11-30 11:11:11.123456
		$test = $test->format('Uv');
		return new MongoDB\BSON\UTCDateTime($test);
	}

	static function timestampToMongo($t, $format = "Y-m-d H:i:s.v"){
		  if(is_object($t)):
		      $utcdatetime = $t;
		  else:
		    $utcdatetime = new MongoDB\BSON\UTCDateTime($t);
		  endif;
		$datetime = $utcdatetime->toDateTime();
		$time = $datetime->format(DATE_RSS);
		$dateInUTC = $time;
		$time = strtotime($dateInUTC.' UTC');
		$dateInLocal = date($format, $time);
		return $dateInLocal;
	}	


}