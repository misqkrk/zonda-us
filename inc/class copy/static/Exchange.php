<?
class Exchange{
	static function tolerance(){
		return 3;
	}

	static function cinkciarz(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

		$url = 'https://cinkciarz.pl/wa/home-rates';
		$json = Curl::single($url,true, 5);
	  
		if($json['currenciesRate']):
			$allowArr = ['EURPLN', 'USDPLN', 'GBPPLN'];
		 
			foreach ($json['currenciesRate'] as $v):
				$name = $v['currenciesPair']['from'].$v['currenciesPair']['to'];

				if(in_array($name,$allowArr)):
					$arr[$name] = [
						'name' => $name,
						'ask' => $v['sell']['rate'],
						'bid' => $v['buy']['rate'],
						'avg' => round(($v['sell']['rate'] + $v['buy']['rate'])/2,4),
						'time' => date('Y-m-d H:i:s'),
					];
				endif;

			endforeach;

			foreach ($arr as $k => $v) :

				$DB->execute("UPDATE CURRENCY_exchange SET
					ask = ".$DB->v($v['ask']).",
					bid = ".$DB->v($v['bid']).",
					avg = ".$DB->v($v['avg']).",
					time = ".$DB->v($v['time'])." 
					WHERE name = '".$k."'
				");

				if($v['name'] == 'USDPLN' || $v['name'] == 'EURPLN' || $v['name'] == 'GBPPLN'):
					$h[$v['name']]['time'] = date("Y-m-d G:i:00",time());
					$h[$v['name']]['avg'] = $v['avg'];    
				endif;

			endforeach;


			$nbp = $DB->fetchAll("SELECT * FROM `CURRENCY_nbp` ORDER BY `data` DESC LIMIT 1 ", null, null, 300)[0];
			if( 
				tolerance_plus_minus($h['EURPLN']['avg'],$nbp['EUR'],Exchange::tolerance())['is_ok'] &&
				tolerance_plus_minus($h['USDPLN']['avg'],$nbp['USD'],Exchange::tolerance())['is_ok'] &&
				tolerance_plus_minus($h['GBPPLN']['avg'],$nbp['GBP'],Exchange::tolerance())['is_ok']
			):

				$arr_update = [
					'data' => $h['EURPLN']['time'],
					'EUR' => $h['EURPLN']['avg'],
					'USD' => $h['USDPLN']['avg'],
					'GBP' => $h['GBPPLN']['avg'],
					'PLN' => '1',

				];
				$DB->insertOrUpdate('CURRENCY_history', $arr_update);

			else:
				send_telegram('ERROR: update_currency_exchange', true);
			endif;

			return 'ok';

	  endif;
	}
//////////////////////////////////
	static function alior(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

		$url = 'https://systemkantor.aliorbank.pl/forex/json/current';
		$json = Curl::single($url,true, 5);

		foreach ($json['currencies'] as $k => $v) :
			$name = $v['currency2'].$v['currency1'];
			$tmp_buy = str_replace(',', '.', $v['buy']);
			$tmp_sell = str_replace(',', '.', $v['sell']);
			$avg = round(($tmp_buy + $tmp_sell)/2,4);
			$allowArr = ['EURPLN', 'USDPLN', 'GBPPLN'];
			
			if(in_array($name,$allowArr)):
				$arr[$name] = [
					'name' => $name,
					'bid' => $tmp_buy,
					'ask' => $tmp_sell,
					'avg' => $avg,
					'time' => $json['lastUpdate'],
				];
			endif;

		endforeach;

		 foreach ($arr as $k => $v) :

			$DB->execute("UPDATE CURRENCY_exchange SET
				ask = ".$DB->v($v['ask']).",
				bid = ".$DB->v($v['bid']).",
				avg = ".$DB->v($v['avg']).",
				time = ".$DB->v($v['time'])." 
				WHERE name = '".$k."'
			");


			if($v['name'] == 'USDPLN' || $v['name'] == 'EURPLN' || $v['name'] == 'GBPPLN'):
				$h[$v['name']]['time'] = date("Y-m-d G:i:00",time());
				$h[$v['name']]['avg'] = $v['avg'];		
			endif;

		 endforeach;

		$nbp = $DB->fetchAll("SELECT * FROM `CURRENCY_nbp` ORDER BY `data` DESC LIMIT 1 ", null, null, 300)[0];
		if( 
			tolerance_plus_minus($h['EURPLN']['avg'],$nbp['EUR'],Exchange::tolerance())['is_ok'] &&
			tolerance_plus_minus($h['USDPLN']['avg'],$nbp['USD'],Exchange::tolerance())['is_ok'] &&
			tolerance_plus_minus($h['GBPPLN']['avg'],$nbp['GBP'],Exchange::tolerance())['is_ok']
		):



			$arr_update = [
				'data' => $h['EURPLN']['time'],
				'EUR' => $h['EURPLN']['avg'],
				'USD' => $h['USDPLN']['avg'],
				'GBP' => $h['GBPPLN']['avg'],
				'PLN' => '1',
			];
			$DB->insertOrUpdate('CURRENCY_history', $arr_update);
		else:
			send_telegram('ERROR: update_currency_exchange', true);
		endif;

		return $json['lastUpdate'];
	}
////////////////////////
	static function update(){
		switch (get_all_option()['EXCHANGE_TYPE']) {
		  case 'CINKCIARZ':
		    return Exchange::cinkciarz();
		  break;

		 
		  case 'ALIOR':
		    return Exchange::alior();
		  break;

		  default:
		    return Exchange::cinkciarz();
		   break;
		}
	}
//////////////////////////
	static function UpdateCourseNBP(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

		$url = 'http://api.nbp.pl/api/exchangerates/tables/a/?format=json';
		$json = Curl::single($url,true);
		if($json[0]['rates']):
			$out['status'] = 'ok';
			$out['date'] = $json[0]['effectiveDate'];

			foreach ($json[0]['rates'] as $v):
				if( in_array($v['code'],FIAT_LIST) ):
					$out['items'][$v['code']] = $v['mid'];

				endif;

			endforeach;

			$arr_update = [
				'data' => $out['date'],
				'EUR' => $out['items']['EUR'],
				'USD' => $out['items']['USD'],
				'GBP' => $out['items']['GBP'],
			];
			$DB->insertOrUpdate('CURRENCY_nbp', $arr_update);

		else:
			$out['status'] = 'error';

		endif;

		return $out;
	}

	static function UpdateCourseBTC(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

		$in = igbinary_unserialize($redis->get('BB:WS:PUBLIC:ticker'));
		if($in['items']['BTC-PLN']):
			$sell = $in['items']['BTC-PLN']['lowestAsk'];
			$buy = $in['items']['BTC-PLN']['highestBid'];
			$avg = ($buy + $sell)/2;
			if($sell > $ask):
				$DB->execute("UPDATE CURRENCY_exchange SET ask='".$sell."', bid='".$buy."', avg='".$avg."', date=NOW() WHERE name='BTC' ");
			endif;
		endif;

		if($in['items']['ETH-PLN']):
			$sell = $in['items']['ETH-PLN']['lowestAsk'];
			$buy = $in['items']['ETH-PLN']['highestBid'];
			$avg = ($buy + $sell)/2;
			if($sell > $ask):
				$DB->execute("UPDATE CURRENCY_exchange SET ask='".$sell."', bid='".$buy."', avg='".$avg."', date=NOW() WHERE name='ETH' ");
			endif;
		endif;


	}




	static function GetCourse(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		
		$results = $DB->fetchAll("SELECT * FROM CURRENCY_exchange", null, 'name', 3, 'course');
		
		foreach ($results as $k => $v):
			$out[$k] = $v['avg'];
		endforeach;

		return $out;	
	}


	static function GetCourseHistory(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

		$now = $DB->fetchAll("SELECT * FROM CURRENCY_exchange", null, 'name', 30);
		$h1 = $DB->fetchAll("SELECT * FROM `CURRENCY_history` WHERE `data` < ( NOW() - INTERVAL 1 HOUR ) ORDER BY `data` DESC LIMIT 1 ", null, null, 30)[0];
		$d1 = $DB->fetchAll("SELECT * FROM `CURRENCY_history` WHERE `data` < ( NOW() - INTERVAL 1 DAY ) ORDER BY `data` DESC LIMIT 1 ", null, null, 30)[0];
		

		foreach ($now as $k => $v):
			$out['now'][$k] = $v['avg'];
		endforeach;

		$out['1d'] = [
			'USD' => roznica_procent( $d1['USD'], $now['USDPLN']['avg'] ),
			'EUR' => roznica_procent( $d1['EUR'], $now['EURPLN']['avg'] ),
			'GBP' => roznica_procent( $d1['GBP'], $now['GBPPLN']['avg'] ),
		];

		$out['1h'] = [
			'USD' => roznica_procent( $h1['USD'], $now['USDPLN']['avg'] ),
			'EUR' => roznica_procent( $h1['EUR'], $now['EURPLN']['avg'] ),
			'GBP' => roznica_procent( $h1['GBP'], $now['GBPPLN']['avg'] ),
		];

		return $out;	
	}


	static function SetWsCourse(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		
		$results = $DB->fetchAll("SELECT * FROM CURRENCY_exchange", null, 'name', 3, 'course');
		
		foreach ($results as $k => $v):
			$o[$k] = $v['avg'];
		endforeach;

		$out['results'] = $o;
		$out['time'] = time();

	   $redis->publish('channel-course', igbinary_serialize( $out ) ); 

		return $out;	
	}
	
}