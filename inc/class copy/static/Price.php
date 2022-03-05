<?
/// na chwile:

function replace_orderbook_binance($json){
	if($json):
		foreach ($json['asks'] as $k => $v):
			$o['Sell'][] = [
				'ra' => (float) $v[0],
				'ca' => (float) $v[1],
			];
		endforeach;
		foreach ($json['bids'] as $k => $v):
			$o['Buy'][] = [
				'ra' => (float) $v[0],
				'ca' => (float) $v[1],
			];
		endforeach;
	endif;
	return $o;	
}



class Price {
	static function calculate($arr, $kupno_cw, $market = null, $cwName = null){ // do poprawienia
		$suma_cw = 0;
		$i = 1;
		$tmp_fiat_arr = ['PLN', 'USD', 'EUR', 'GBP', 'USDC', 'USDT'];
		if(in_array($market, $tmp_fiat_arr)):
			$round = 2;
		else:
			$round = 8;
		endif;

		foreach ($arr as $key => $value):
			if($suma_cw <= $kupno_cw):

	      	$course = $value['ra'];
	      	$amount = $value['ca'];
	      	$price = $course * $amount;
	      	
	      	$cw = $amount;
	      	$suma_cw = $suma_cw + $cw;
		
	      	$fiat = $price;
	      	$suma_fiat = $suma_fiat + $fiat;
		
	      	$out[$key]['course'] = $end_rate = $course;
	      	$out[$key]['amount'] = $amount;
	      	$out[$key]['price'] = $price;
	      	$out[$key]['sum_fiat'] = $suma_fiat;
	      	$out[$key]['sum_cw'] = $suma_cw;
	      	$out[$key]['i'] = $i++;
			endif;
		
		endforeach;

		if($out):
			$last = end($out);
			
			if($last['sum_cw'] > $kupno_cw):
				$odejmowanie_cw = $last['sum_cw'] - $kupno_cw;
				$sum_fiat = $last['sum_fiat'] - ($odejmowanie_cw * $last['course']);
				$o1['score'] = [
					'sum_fiat' => round($sum_fiat, $round), //// zle do poprawyt
					'sum_fiat_all' => round($sum_fiat, 8), //// zle do poprawyt

					'market' => $market, 
					'cw' => $cwName, 
					'round' => $round,

					'amount' => $kupno_cw,
					'course' => round($sum_fiat / $kupno_cw, $round),
					'end_rate' => $end_rate,
					'i' => $last['i'],

				];
			else:
				$o1['score'] = [
					'sum_fiat' => round($last['sum_fiat'], $round), //// zle do poprawyt
					'sum_fiat_all' => round($last['sum_fiat'], 8), //// zle do poprawyt

					'market' => $market, 
					'cw' => $cwName, 

					'round' => $round, 
					'amount' => $last['sum_cw'],
					'course' => round($last['sum_fiat'] / $last['sum_cw'], $round),
					'end_rate' => $end_rate,
					'i' => $last['i'],

				];		
			endif;

			$ooo['items'] = $out;
			$ooo['score'] = $o1['score'];

			return $ooo;

		else:
			return null;
		endif;
	}



static function calculatePrice($arr, $in_price, $market = null, $cwName = null){ // poprawione - zwykle tak samo napisac
		$sum_amount = 0;
		$sum_price = 0;
		$tmp_sum_price = 0;
		$i = 1;
		$tmp_fiat_arr = ['PLN', 'USD', 'EUR', 'GBP', 'USDC', 'USDT'];
		if(in_array($market, $tmp_fiat_arr)): $round = 2;
		else: $round = 8;
		endif;

		foreach ($arr as $key => $value):
			if($tmp_sum_price <= $in_price):

	      	$rate = $value['ra'];
	      	$amount = $value['ca'];
	      	$price = $rate * $amount;
	      	$tmp_sum_price += $price;

	      	if($tmp_sum_price > $in_price):
	      		$excess_price = $tmp_sum_price - $in_price;
	      		$new_price = $price - $excess_price; //??
	      		$new_amount = $new_price / $rate;

	      		$sum_amount += $new_amount;
			  		$sum_price += $new_price;

	      		$out[$key] = [
	      			'excess_price' => $excess_price,
						'rate' => $end_rate = round($rate, $round),
	      			'amount' => round($new_amount,8),
	      			'price' => round($new_price, $round),
						'sum_price' => round($sum_price, $round),
						'sum_amount' => round($sum_amount,8),
						'i' => $i,
						'virtual' => true,
					];
				else:
					$sum_amount += $amount;
			   	$sum_price += $price;	

					$out[$key] = [
						'rate' => $end_rate = round($rate, $round),
						'amount' => round($amount,8),
						'price' => round($price, $round),
						'sum_price' => round($sum_price, $round),
						'sum_amount' => round($sum_amount,8),
						'i' => $i,
					];
	      	endif;
	      	$i++;
			endif;
		
		endforeach;

		$last = end($out);
		
		if($last['sum_price']):
			$o1['score'] = [
				'cw' => $cwName,
				'market' => $market,
				'price' => round($last['sum_price'], $round),
				'rate' => round($last['sum_price'] / $last['sum_amount'], $round),
				'amount' => round($last['sum_amount'],8),
				'end_rate' => round($end_rate, $round),
				'round' => $round,
				'i' => $last['i'],
			];
		endif;

		$ooo['items'] = $out;
		$ooo['score'] = $o1['score'];
		return $ooo;
	}


	static function rest($cw, $rynek, $ilosc, $typ = 'sell'){
		$typ = strtolower($typ);
		if($cw && $rynek && $ilosc):
			$arr = [
			  $cw => [$rynek]
			];
			if($typ == 'sell'):
			  $type = 'Buy';
			elseif($typ == 'buy'):
			    $type = 'Sell';
			endif;
			$WS = new WebsocketV2;
			$arr = $WS->getPublicOrderbook([$cw => [$rynek]])[$cw.'-'.$rynek][$type];

			$result = Price::calculate($arr, $ilosc, $rynek, $cw);
			$result['score']['type'] = strtoupper($typ);

		endif;
		return $result;
	}



	static function restPrice($cw, $rynek, $price, $typ = 'sell'){
		$typ = strtolower($typ);
		if($cw && $rynek && $price):
			$arr = [
			  $cw => [$rynek]
			];
			if($typ == 'sell'):
			  $type = 'Buy';
			elseif($typ == 'buy'):
			    $type = 'Sell';
			endif;
			$WS = new WebsocketV2;
			$arr = $WS->getPublicOrderbook([$cw => [$rynek]])[$cw.'-'.$rynek][$type];

			$result = Price::calculatePrice($arr, $price, $rynek, $cw);
		endif;
		return $result;
	}


	static function ws($cw, $market, $ilosc, $typ = 'sell'){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$typ = strtolower($typ);

		if($cw && $market && $ilosc):
			$arr = [
			  $cw => [$market]
			];
			if($typ == 'sell'):
			  $type = 'Buy';
			elseif($typ == 'buy'):
			    $type = 'Sell';
			endif;

    		$arr = igbinary_unserialize($redis->get('BB:WS:PUBLIC:ORDERBOOK:'.$cw))[$cw.'-'.$market][$type];    
			$result = Price::calculate($arr, $ilosc, $market, $cw);
			$result['score']['type'] = strtoupper($typ);
		endif;
		return $result;
	}

	static function wsPrice($cw, $market, $price, $typ = 'sell'){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$typ = strtolower($typ);

		if($cw && $market && $price):
			$arr = [
			  $cw => [$market]
			];
			if($typ == 'sell'):
			  $type = 'Buy';
			elseif($typ == 'buy'):
			    $type = 'Sell';
			endif;

    		$arr = igbinary_unserialize($redis->get('BB:WS:PUBLIC:ORDERBOOK:'.$cw))[$cw.'-'.$market][$type];    
			$result = Price::calculatePrice($arr, $price, $market, $cw);

		endif;
		return $result;
	}


	static function restBinance($cw, $rynek, $ilosc, $typ = 'sell'){
		$typ = strtolower($typ);
		if($cw && $rynek && $ilosc):


			$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

			if(! $json = igbinary_unserialize($redis->get('BINANCE:REST:ORDERBOOK:LIMIT-20:'.$cw.'-'.$rynek) )):
				$url = 'https://api.binance.com/api/v3/depth?symbol='.$cw.$rynek.'&limit=20';
				$json = Curl::single($url,true, 5);

				$redis->set('BINANCE:REST:ORDERBOOK:LIMIT-20:'.$cw.'-'.$rynek, igbinary_serialize($json), 2);
			endif;

			$orderbook = replace_orderbook_binance($json);

			$arr = [
			  $cw => [$rynek]
			];
			if($typ == 'sell'):
			  $type = 'Buy';
			elseif($typ == 'buy'):
			    $type = 'Sell';
			endif;
			$arr = $orderbook[$type];

			$result = Price::calculate($arr, $ilosc, $rynek);
		endif;
		return $result;
	}


}