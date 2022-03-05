<?
class FC {

// fiat_cantor_exchange -> FC::exchange
	static function info($in = 'PLN', $out = 'EUR'){
		$API = new API(BB_API);
		$req = $API->SignedCallApi('fiat_cantor/rate/'.$in.'/'.$out);
		return $req;

	}

	static function exchange($in, $out, $amount = 10, $type = 'n/a', $rate_in = null){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		
		if( ($in = strtoupper($in) )  && ( $out = strtoupper($out) ) ):
			$API = new API(BB_API);
			
			do{ 
				$req = $API->SignedCallApi('fiat_cantor/rate/'.$in.'/'.$out);
				if($req['status'] == 'Ok'):
					$results = $DB->fetchAll("SELECT * FROM `BB_balances` WHERE `type` = 'FIAT'", null, 'name');
		         
		         $currency1 = $in;
		         $currency1BalanceId = $results[$currency1]['id'];
		         $currency2 = $out;
		         $currency2BalanceId = $results[$currency2]['id'];

					$send = [
		            'currency1' => $currency1,
		            'currency2' => $currency2,
		            'currency1BalanceId' => $currency1BalanceId,
		            'currency2BalanceId' => $currency2BalanceId,
		            'amount' => $amount,
		            //'amount' => 1.5,
		            'rate' => $rate_in ?? $req['rate'],
					];
					$o['send'] = $send;
					$o['out'] = $API->SignedCallApi('fiat_cantor/exchange',$send, 'POST');
					$o['i'][] = true;

					if($o['out']['status'] == 'Ok'):
						$tmp_given_my_rate =  round(courseTo($amount, $currency1, $currency2), 2);
						$tmp_difference = $tmp_given_my_rate - $o['out']['amount'];
						$tmp_difference_pln = round(courseTo($tmp_difference, $currency2, 'PLN'), 2);
						$o['fee_pln'] = $tmp_difference_pln;

			         $add_sql = [
			         	'currency1' => $currency1,
			         	'currency2' => $currency2,
			         	'amountGiven' => $amount,
			         	'amountReceived' => $o['out']['amount'],
			         	'amountGivenPLN' => courseTo($amount,$currency1),
			         	'rate' => $req['rate'],
			         	'type' => $type,
			         	'fee' => $tmp_difference_pln,
			         	'date' => '++NOW()',
			         ];
			         $DB->insertOne('BB_fc_history', $add_sql);
		      	endif;

				endif;

				if($o['out']['status'] == 'Ok') break;
				slaap(0.1);
			}	while(  $i++ < 2 );
			
			BALANCE::cron();
			return $o;

		else:
			return false;
			// brak in lub out
		endif;
	}




	//fiat_cantor_ap_memcached -> FC::actual_percent
	static function actual_percent($pair = null){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

		if($tmp = igbinary_unserialize($redis->get('BB:FC:actual_percent') )):
			//send_telegram('test3');
			FC::status($tmp); // ustalenie statusu i wyslanie wiadomosci - potem do crona?
		else:
			$tmp = FC::p();
			FC::status($tmp); // ustalenie statusu i wyslanie wiadomosci - potem do crona?
			$redis->set('BB:FC:actual_percent', igbinary_serialize($tmp), 60);
		endif;

		if($pair):
			$tmp_new[$pair] = $tmp[$pair];
			unset($tmp);
			$tmp = $tmp_new;
		endif; 

		return $tmp;
	}

	//fiat_cantor_status -> FC::status
	static function status($in){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		
		$fc_status = igbinary_unserialize($redis->get('BB:FC:cantor_status') );

		if($tmp_p = $in['PLN-USD']['percent_my']):
			
			if( ($tmp_p < 0.59) && ($tmp_p > 0.1) ): $out = ['p' => 0.59, 'i' => 1, 'html'=> 'Very Excelent'];
			elseif($tmp_p < 0.79): $out = ['p' => 0.79, 'i' => 2, 'html'=> 'Excelent'];
			elseif($tmp_p < 0.97): $out = ['p' => 0.97, 'i' => 3, 'html'=> 'Good'];
			elseif($tmp_p < 1.19): $out = ['p' => 1.19, 'i' => 4, 'html'=> 'Bad'];
			elseif($tmp_p < 1.5): $out = ['p' => 1.5, 'i' => 5, 'html'=> 'Very Bad'];
			else: $out = ['p' => 2, 'i' => 10, 'html'=> 'Extremely Bad'];
			endif;
		

			if( $fc_status['i'] == $out['i']):
				//Nic się nie zmieniło
				//send_telegram('test1');
			else:
				if($fc_status):
					//Zmieniło się - wysyłamy wiadomość na telegram. 
					$msg .= "\xF0\x9F\x94\x94 ";
					$msg .= '<b>'.$fc_status['html'].'</b> => <b>'.$out['html'].'</b>';
					//send_telegram($msg); WYLACZONE OBECNIE

					$redis->set('BB:FC:cantor_status', igbinary_serialize($out), 43200); //12h

				else:
					//Zmieniło się - Nie ma w mem - zapisujemy
					$redis->set('BB:FC:cantor_status', igbinary_serialize($out), 43200); //12h
				endif;
			endif;

			return $out;

		endif;	
	}



	//fiat_cantor_p -> FC::p
	static function p($pair = null){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

		$tmp = igbinary_unserialize($redis->get('BB:WS:PUBLIC:fiat_cantor') );

		$my_reduction = 0.0;
		$arr_map = [
			'PLN-EUR' => 1,
			'EUR-PLN' => 2,

			'PLN-USD' => 1,
			'USD-PLN' => 2,

			'PLN-GBP' => 1,
			'GBP-PLN' => 2,

			'EUR-USD' => 3,
			'USD-EUR' => 4,

			'GBP-USD' => 3,
			'USD-GBP' => 4,

			'EUR-GBP' => 3,
			'GBP-EUR' => 4,

		];

		if($pair):
			$tmp_new[$pair] = $tmp[$pair];
			unset($tmp);
			$tmp = $tmp_new;
		endif; 

		if($tmp):
			foreach ($tmp as $k => $v):
				$map = $arr_map[$k];
				$ex = explode('-', $k);

				if($map == 1):
					$rate_my = round( procent($v['rate'], $my_reduction, '-' ), 4);
					$percent = roznica_procent(courseTo(1,$ex[1],$ex[0]) , $v['rate'] );
					$percent_my = roznica_procent(courseTo(1,$ex[1],$ex[0]) , $rate_my );

				elseif($map == 2):
					$rate_my = round( procent($v['rate'], $my_reduction, '+' ), 4);
					$percent = roznica_procent($v['rate'], courseTo(1,$ex[0],$ex[1]) );
					$percent_my = roznica_procent($rate_my, courseTo(1,$ex[0],$ex[1]) );

				elseif($map == 3):
					$rate_my = round( procent($v['rate'], $my_reduction, '+' ), 4);
					$percent = roznica_procent($v['rate'], courseTo(1,$ex[0],$ex[1]) );
					$percent_my = roznica_procent($rate_my, courseTo(1,$ex[0],$ex[1]) );

				elseif($map == 4):
					$rate_my = round( procent($v['rate'], $my_reduction, '-' ), 4);
					$percent = roznica_procent(courseTo(1,$ex[1],$ex[0]) , $v['rate'] );
					$percent_my = roznica_procent(courseTo(1,$ex[1],$ex[0]) , $rate_my );

				endif;


				$out[$k] = [
					'k' => $k,
					'p1' => $ex[0],
					'p2' => $ex[1],
					'rate_BB' => $v['rate'],
					'rate_BB_my' => $rate_my,
					'percent' => $percent,
					'percent_my' => $percent_my,
					'map' => $map,

				];

			endforeach;

			return $out;
		else:
			return null;
		endif;
	}

	//fiat_cantor_s -> FC::s
	static function s($pair1, $pair2, $t = 1){ //'PLN-USD', 'USD-PLN'
		$arr = FC::p();
		$ex = explode('-', $pair1);

		if($t == 1): 
			$course = round(courseTo(1,$ex[1], $ex[0]),4);
			$spread = roznica_procent($arr[$pair2]['rate_BB'],$arr[$pair1]['rate_BB'],2);
			$spread_my = roznica_procent($arr[$pair2]['rate_BB_my'],$arr[$pair1]['rate_BB_my'],2);

		elseif($t == 2): 
			$course = round(courseTo(1,$ex[0], $ex[1]),4);
			$spread = roznica_procent($arr[$pair1]['rate_BB'],$arr[$pair2]['rate_BB'],2);
			$spread_my = roznica_procent($arr[$pair1]['rate_BB_my'],$arr[$pair2]['rate_BB_my'],2);

		endif;

		$out = [
			'pair1' => $arr[$pair1],
			'pair2' => $arr[$pair2],
			'd' => [
				'course' => $course,
				'spread' => $spread,
				'spread_my' => $spread_my,
				'rate_BB' => round( ($arr[$pair2]['rate_BB'] + $arr[$pair1]['rate_BB']) /2 ,4),

			],
		];

		return $out;
	}

	//update_FC_cost -> FC::update_cost
	static function update_cost(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$in = igbinary_unserialize($redis->get('BB:FC:actual_percent'));
		if($in):

			$add_sql = [
				'PLN-GBP' => $in['PLN-GBP']['percent_my'],
				'GBP-PLN' => $in['GBP-PLN']['percent_my'],
				'USD-PLN' => $in['USD-PLN']['percent_my'],
				'EUR-PLN' => $in['EUR-PLN']['percent_my'],
				'PLN-EUR' => $in['PLN-EUR']['percent_my'],
				'PLN-USD' => $in['PLN-USD']['percent_my'],
				'GBP-EUR' => $in['GBP-EUR']['percent_my'],
				'EUR-GBP' => $in['EUR-GBP']['percent_my'],
				'USD-GBP' => $in['USD-GBP']['percent_my'],
				'EUR-USD' => $in['EUR-USD']['percent_my'],
				'GBP-USD' => $in['GBP-USD']['percent_my'],
				'USD-EUR' => $in['USD-EUR']['percent_my'],
			];
			$DB->insertOne('BB_FC_cost', $add_sql);
		endif;
	}




	/// TESTS NEW FUNCTION CHOICE PLAN
	static function update_choice_plan_orders(){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();


		$redis->incr('COUNT:orders:2h');
		$redis->expire('COUNT:orders:2h', 3600);

		$redis->incr('COUNT:orders:15m');
		$redis->expire('COUNT:orders:15m', 900);
	}

	static function get_choice_plan_orders(){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$ALL_OPTION = $_ENV[PROJECT]['all_config'] = $_ENV[PROJECT]['all_config'] ?? get_all_option();

		$get_2h = $redis->get('COUNT:orders:2h');
		$get_15m = $redis->get('COUNT:orders:15m');

		if($get_2h && empty($get_15m) ): $plan = 'normal';
		elseif($get_2h && $get_15m): // coś jest poniżej 15 min
			if($get_15m >= 5): $plan = 'increased';
			else: $plan = 'normal';
			endif;
		else: $plan = 'reduced';
		endif;


		if($ALL_OPTION['FC_REVERSE_MODE'] == 'NORMAL'):
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT'];
			$active_plan_name = 'normal';
			$type = 'MANUAL';

		elseif($ALL_OPTION['FC_REVERSE_MODE'] == 'REDUCED'):
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT_REDUCED'];
			$active_plan_name = 'reduced';
			$type = 'MANUAL';
			
		elseif($ALL_OPTION['FC_REVERSE_MODE'] == 'INCREASED'):
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT_INCREASED'];
			$active_plan_name = 'increased';
			$type = 'MANUAL';
	
		elseif($plan == 'increased'):
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT_INCREASED'];
			$active_plan_name = 'increased';
			$type = 'AUTO';

		elseif($plan == 'normal'):
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT'];
			$active_plan_name = 'normal';
			$type = 'AUTO';

		elseif($plan == 'reduced'):
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT_REDUCED'];
			$active_plan_name = 'reduced';
			$type = 'AUTO';

		else:
			$active_plan_percent = (float) $ALL_OPTION['FC_REVERSE_PERCENT'];
			$active_plan_name = 'normal';
			$type = 'ELSE';

		endif;


		$out = [
			'automat' => $plan,
			'active_plan_percent' => $active_plan_percent,
			'active_plan_name' => $active_plan_name,
			'type' => $type,
		];
		return $out;
	}


	static function set_ws_choice_plan_orders(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		
		$out = FC::get_choice_plan_orders();
	   $redis->publish('channel-choice_plan_orders', igbinary_serialize( $out ) ); 

		return $out;	
	}
	/// END TESTS NEW FUNCTION CHOICE PLAN



}