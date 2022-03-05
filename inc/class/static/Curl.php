<?
class Curl{
## $j = true = json to array
## Curl::single 
## Curl::multi array
	
	static function headers(){
		return [
			'Accent: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Language: pl,en-US;q=0.8,en;q=0.6,de;q=0.4',
			'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
			'Keep-Alive: 115'
		];
	}

	static function agents(){
		$agents  = [
			'Googlebot/2.1 (+http://www.google.com/bot.html)',
			//'Mozilla/5.0 (X11; Linux i686; rv:2.0b12pre) Gecko/20110218 Firefox/4.0b12pre'
			//'Opera/9.80 (Windows NT 5.1; U; pl) Presto/2.5.24 Version/10.54',
			//'Opera/9.80 (Windows NT 6.1; U; Edition Campaign 21; pl) Presto/2.5.24 Version/10.53'
		];		
		return $agents[array_rand($agents)];
	}

	static function single($url = null , $j = false, $timeout = 5){
		$curl = curl_init();

		//curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_URL, $url);
		//curl_setopt($curl, CURLOPT_USERAGENT, self::agents() );
		curl_setopt($curl, CURLOPT_HTTPHEADER, self::headers() );
		//curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($curl, CURLOPT_AUTOREFERER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	  
		$result = curl_exec($curl);
		
	   if($result === false):
	   	echo curl_error($curl);
	   else:

			if($j){
				try {
				    return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
				} catch (\JsonException $e) {
					echo $url.'::';
				   echo $e->getCode().'::'; // 4
				   echo $e->getMessage(); // Syntax error
				}

			}else{
				return $result;
			}

		endif;

		curl_close($curl);

		
	}

	static function multi($urls = [] , $j = false, $timeout = 5){
		foreach ($urls as $k => $v) {
			$ch[$k] = curl_init($v);
			curl_setopt($ch[$k], CURLOPT_HTTPHEADER, self::headers() );
			curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch[$k], CURLOPT_ENCODING, 'gzip,deflate');
			curl_setopt($ch[$k], CURLOPT_AUTOREFERER, true);
			curl_setopt($ch[$k], CURLOPT_FOLLOWLOCATION, true);
			//curl_setopt($ch[$k], CURLOPT_DNS_USE_GLOBAL_CACHE, true);
			curl_setopt($ch[$k], CURLOPT_TIMEOUT, $timeout);
		}

		$mh = curl_multi_init();

		foreach ($urls as $k => $v) {
			curl_multi_add_handle($mh, $ch[$k]);
		}

		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);

		foreach ($urls as $k => $v) {
			curl_multi_remove_handle($mh, $ch[$k]);
		}

		curl_multi_close($mh);
  
		foreach ($urls as $k => $v) {
			if($j){

				try {
				    $out[$k] = json_decode( curl_multi_getcontent($ch[$k]), true, 512, JSON_THROW_ON_ERROR);
				} catch (\JsonException $e) {
					echo $v.'::';
				   echo $e->getCode().'::'; // 4
				   echo $e->getMessage().PHP_EOL; // Syntax error
				}

			}else{
				$out[$k] = curl_multi_getcontent($ch[$k]);
			}
		}

		return $out; //array
	}

}