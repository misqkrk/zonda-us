<?
class PowiadomieniaV2 {

	public function __construct($in, $ws_time = null) {

      $this->redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
      $this->DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

      $this->GLOBAL = $_ENV;
      $this->TRADING_CONFIG = GET_TRADING_CONFIG;
      $this->FIAT_LIST = FIAT_LIST;
		$this->ALL_OPTION =  $this->GLOBAL['all_config'] ?? get_all_option();
		$this->PP = $this->GLOBAL['pp'] ?? bot_pp_V2() ;

		$this->in = $in;
		$this->ws_time = $ws_time;
	}
	public function set_fc(){


		
		$active_plan_percent = (float) $this->GLOBAL['choice_plan_orders']['active_plan_percent'];

		if($this->ALL_OPTION['FC_REVERSE_ON'] && $this->is_fiat_to_fiat() && $this->is_fc_reverse_correct() ): // JEŻELI WŁĄCZONE && FIAT TO FIAT
			

			if((float) $this->PP[$this->arr['tmp']['pair']]['percent'] >= (float) $this->ALL_OPTION['FC_BALANCES_PERCENT'] && $this->PP[$this->arr['tmp']['pair']]['fc_active']): // JEŻĘLI MAŁO NA STANIE


				if(
					$this->ALL_OPTION['FC_NIGHT_ON']
					
					&& ( ( (float) $this->arr['percent'] - (float) $this->PP[$this->arr['tmp']['pair']]['planning_exchange_reverse']) >= $active_plan_percent ) 
					&& $this->is_night() 
					&& ( (float) $this->PP[$this->arr['tmp']['pair']]['percent'] < $this->ALL_OPTION['FC_NIGHT_MIN_BALANCE']) 
					):
					$run = 2;

				elseif( (string)( $this->arr['percent'] - $this->PP[$this->arr['tmp']['pair']]['fc_reverse']) >= (string) $active_plan_percent):
					$run = 1;

				endif;	

			endif;


		endif;

		$out = [
			'setting' => [
				'on' => $this->ALL_OPTION['FC_REVERSE_ON'],
				'percent' => $active_plan_percent,
				'balances_percent' => (float) $this->ALL_OPTION['FC_BALANCES_PERCENT'],
				'choice_plan_orders' => $this->GLOBAL['choice_plan_orders']['active_plan_name'],
			],
			'info' => [
				'reverse_percent' => (float) $this->PP[$this->arr['tmp']['pair']]['fc_reverse'],
				'balance_percent' => (float) $this->PP[$this->arr['tmp']['pair']]['percent'],
			],
			'realization' => [
				'is_fiat_to_fiat' => $this->is_fiat_to_fiat(),
				'percent' => (float) ( $this->arr['percent'] - $this->PP[$this->arr['tmp']['pair']]['fc_reverse'] ),
			],
			'fc_active' => $this->PP[$this->arr['tmp']['pair']]['fc_active'],
			'is_night' => $this->is_night(),
			'run' => $run,
		];
		//print_r($out);
		return $out;		
	}


	public function set_array($v){
		$out = [
			'cw' => $v['info']['cw'],                         // cw
			'pair' => $v['info']['pair'],    // pair
			'B_rate' => $v['BUY']['rate'],        // B:rate
			'B_market' => $v['BUY']['market'],           // B:market
			'S_rate' => $v['SELL']['rate'],       // S:rate  
			'S_market' => $v['SELL']['market'],          // S:market
			'B_rate_pln' => $v['BUY']['rate_pln'],     // B:rate_pln  
			'S_rate_pln' => $v['SELL']['rate_pln'],    // S:rate_pln
			'price_difference' => $v['info']['price_difference'], // D:price  
			'amount_all' => $v['info']['all']['amount'], // do statow   //D:amount
			'profit_all' => $v['info']['all']['profit'], // do statow  //D:profit
			'balances' => $v['balances']['BUY'],                // balances
			'amount_available' => $v['info']['amount'], // wazniejsze    // D:amount_available
			'profit_available' => $v['info']['profit'], // wazniejsze   // D:profit_available
			'percent' => $v['info']['percent'],                   //D:percent
			'mID' => $v['info']['mID'], 				//orderID
			'direction' => $v['info']['direction'],          //direction
			'direction_code' => $v['info']['direction_code'],       //direction_code
			'time' => $v['info']['time'],			                    //time
			'ws_send_time' => $this->ws_time,                        //time_ws
			'arr_porownanie' => $v,
			'json' => null,
			//'json' => str_replace("'","\'",serialize($v['json'])),

			'tmp' => [ 
				'sleep' => $v['info']['sleep'],
				'my_offer' => $v['info']['my_offer'],
				'B_price' => $v['BUY']['price'],
				'S_price' => $v['SELL']['price'],
				'B_price_pln' =>  round($v['BUY']['rate_pln'] * $v['info']['amount'], 2),
				'pair' => $v['info']['pair'],

			],
		];
		//print_r($out);
		return $out;
	}

	public function is_fiat_to_fiat(){ 
		if( in_array($this->arr['B_market'], $this->FIAT_LIST)  && in_array($this->arr['S_market'], $this->FIAT_LIST) ): return true;
		else: false;
		endif;		
	}

	public function is_fc_reverse_correct(){ 
		if( (float) $this->PP[$this->arr['tmp']['pair']]['fc_reverse'] > 0.1): return true;
		else: false;
		endif;
	}

	public function is_fc_reverse_night_correct(){ 
		if( (float) $this->PP[$this->arr['tmp']['pair']]['planning_exchange_reverse'] > 0.1): return true;
		else: false;
		endif;
	}

	public function is_night(){ 
		if($this->is_fc_reverse_night_correct() && ( (float)$this->PP[$this->arr['tmp']['pair']]['fc_reverse'] > (float)$this->PP[$this->arr['tmp']['pair']]['planning_exchange_reverse'])): return true;
		else: false;
		endif;
	}



	public function is_correct(){
		if( ( (float) $this->arr['percent'] >= -2 && (float) $this->arr['percent'] < 1500) && ( (float) $this->arr['B_rate'] > 0) && ( (float) $this->arr['S_rate'] > 0) ): return true;
		else: return false; // FALSE - na czas testów może być true
		endif;
	}

	public function is_my_offer(){
		if( $this->arr['tmp']['my_offer'] == 1 ): return true;
		else: return false;
		endif;
	}

	public function is_sleep(){
		if( $this->arr['tmp']['sleep']): return false;
		else: return true;
		endif;
	}


	public function is_min_price(){
		if(
			( (float) $this->arr['amount_available'] >= (float) $this->TRADING_CONFIG[$this->arr['cw'].'-'.$this->arr['B_market']]['firstMinValue'] )
			&& ( (float) $this->arr['tmp']['B_price'] >= (float) $this->TRADING_CONFIG[$this->arr['cw'].'-'.$this->arr['B_market']]['secondMinValue'] )
			&& ( (float) $this->arr['tmp']['S_price'] >= (float) $this->TRADING_CONFIG[$this->arr['cw'].'-'.$this->arr['S_market']]['secondMinValue'] )			
		): return true;
		else: return false;
		endif;
	}

	public function is_min_price_add(){ // sprawdza czy add buy/sell jest poniżej minimum - jeżeli to oferta bez add zwraca true
		if(!$this->arr['arr_porownanie']['ADD']):
			return true;
		else:
			if(
				( (float) $this->arr['arr_porownanie']['ADD']['amount'] >= (float) $this->TRADING_CONFIG[$this->arr['arr_porownanie']['ADD']['pair']]['firstMinValue'] )
				&& ( (float) $this->arr['arr_porownanie']['ADD']['price'] >= (float) $this->TRADING_CONFIG[$this->arr['arr_porownanie']['ADD']['pair']]['secondMinValue'] )
			):
				return true;
			else:
				return false;
			endif;

		endif;
	}


	public function is_duplicate(){
		if( $this->redis->exists('TMP:orderID:'.$this->arr['mID']) ): return true;
		else: return false;
		endif;
	}

	public function set_duplicate($time = 300){
		$this->redis->set('TMP:orderID:'.$this->arr['mID'], true, $time);
	}


	public function values($send = 0){
		$out = [
			'orderID' => $this->arr['mID'],
			'cw' => $this->arr['cw'],
			'B:rate' => $this->arr['B_rate'],
			'B:market' => $this->arr['B_market'],
			'S:rate' => $this->arr['S_rate'],
			'S:market' => $this->arr['S_market'],
			'B:rate_pln' => $this->arr['B_rate_pln'],
			'S:rate_pln' => $this->arr['S_rate_pln'],
			'D:price' => $this->arr['price_difference'],
			'D:amount' => $this->arr['amount_all'],
			'D:percent' => $this->arr['percent'],
			'D:profit' => $this->arr['profit_all'],
			'balances' => $this->arr['balances'],
			'D:amount_available' => $this->arr['amount_available'],
			'D:profit_available' => $this->arr['profit_available'],
			'direction_code' => $this->arr['direction_code'],
			'direction' => $this->arr['direction'],
			'last' => '++NOW()',
			'time' => $this->arr['time'],
			'send' => $send,
			//'json' => $this->arr['json'],
		];
		return $out;
	}

	public function set_SQL($values){

		if($values):
			$key = $this->DB->insertMulti('B1_transaction_list', $values, 'INSERT IGNORE INTO');

		endif;
	}


	public function del_ws_redis(){
		$this->redis->delete('INFO:WS:REDIS:'.$this->arr['cw'].'#'.getmypid());
	}

	public function notification(){
		$msg .= icon_send_v2('info').$this->icon($this->arr['profit_available'])['up_number'];
		$msg .= '<b>'.$this->arr['cw'].':: '.$this->arr['tmp']['pair'].'</b>'.PHP_EOL;
		$msg .= 'PERCENT: <b>'.$this->arr['percent'].'%</b>'.PHP_EOL;
		$msg .= 'PROFIT: <b>'.my_number(round($this->arr['profit_available'],2)).'</b> PLN'.PHP_EOL;
		$msg .= 'AMOUNT: '.number_format($this->arr['amount_available'],8,'.','').' '.$this->arr['cw'].' ('.my_number($this->arr['tmp']['B_price_pln']).' PLN)'.PHP_EOL;
		$msg .= '--------------------------------'.PHP_EOL;
		$msg .= 'DIR: '.$this->arr['direction'].' | ('.my_number(round($this->arr['price_difference'],2)).' PLN)'.PHP_EOL;

		if($this->arr['profit_available'] != $this->arr['profit_all']):
			$msg .= 'A: '.number_format($this->arr['amount_all'],8,'.','').' '.$this->arr['cw'].' ('.$this->arr['tmp']['B_price_pln'].' PLN)'.PHP_EOL;
			$msg .= 'Profit: '.round($this->arr['profit_all'],2).' PLN'.PHP_EOL;
		endif;
		
		$msg .= 'BUY RATE: '.$this->arr['B_rate'].' '.$this->arr['B_market'].PHP_EOL;
		$msg .= 'SELL RATE: '.$this->arr['S_rate'].' '.$this->arr['S_market'].PHP_EOL;
		$msg .= '<i>Current percentage: '.$this->PP_bot.'%.</i>'.PHP_EOL;
		
		// if($this->arr['fc']['realization']['is_fiat_to_fiat']):
		// 	$msg .= '<i>FC Reverse: '.$this->arr['fc']['info']['reverse_percent'].' %</i>'.PHP_EOL;
		// 	$msg .= '<i>PERCENT: '.$this->arr['fc']['info']['balance_percent'].' %</i>'.PHP_EOL;
		// endif;
		//$msg .= '<i>POWIADOMIENIA CLASS v1</i>'.PHP_EOL;

		send_telegram($msg, true, $this->add_keyboard(), true);
	}


	public function add_keyboard(){
		$out[0][] = ['BUY', 'get-z|wykonaj#'.$this->arr['mID']];
		if($this->arr['fc']['realization']['is_fiat_to_fiat']):
			$out[0][] = ['BUY + FC', 'get-z|buy_fc#'.$this->arr['mID']]; 
		endif;
		$out[0][] = ['DETAILS', 'get-z|zobacz#'.$this->arr['mID']];
		$out[1][] = ['OFF', 'get-z|sleep#0'];
		$out[1][] = ['2H', 'get-z|sleep#120'];
		$out[1][] = ['4H', 'get-z|sleep#240'];
		$out[1][] = ['8H', 'get-z|sleep#480'];
		$out[1][] = ['12H', 'get-z|sleep#720'];
		return $out;
	}



	public function run(){
		if(isset($this->in['porownanie'])):
			foreach ($this->in['porownanie'] as $k => $vv):
				if(isset($vv['array'])):
					foreach($vv['array'] as $v):
						$this->arr = $this->set_array($v);
						$this->arr['fc'] = $this->set_fc();
						

						if( $this->is_correct() && !$this->is_my_offer() && $this->is_min_price() && $this->is_min_price_add() && !$this->is_duplicate() ):
							$this->PP_bot = (float) $this->PP[$this->arr['tmp']['pair']]['bot'];
							
							if( ( (float) $this->arr['percent'] >= $this->PP_bot) && $this->ALL_OPTION['BOT_ON'] && $this->is_sleep()  ): // KUPUJEMY - Większe lub równe min percent
								$values[] = $this->values(2);

								///test
								$this->redis->set('B1Run:'.$this->arr['mID'], true);
								//end test

								start_order_V2($this->arr['mID'], 'bot', $this->arr); // wykonaj automat
								$c = '2';
								
								$this->set_SQL($values);

								$this->del_ws_redis(); 
								die('RUN B1: DIE || '.$this->arr['tmp']['pair']);

							elseif( ($this->arr['fc']['run'] == 1) && $this->ALL_OPTION['BOT_ON'] && $this->is_sleep() ): // KUPUJEMY - wyzwolone przez FC
								$values[] = $this->values(3);
								start_order_V2($this->arr['mID'], 'bot', $this->arr); // wykonaj automat
								$c = '3';

								$this->set_SQL($values);

								$this->del_ws_redis(); 
								die('RUN B1: DIE || '.$this->arr['tmp']['pair']);

							elseif( ($this->arr['fc']['run'] == 2) && $this->ALL_OPTION['BOT_ON'] && $this->is_sleep() ): // KUPUJEMY - wyzwolone przez FC
								$values[] = $this->values(4);
								start_order_V2($this->arr['mID'], 'bot', $this->arr); // wykonaj automat
								$c = '4';

								$this->set_SQL($values);
								
								$this->del_ws_redis(); 
								die('RUN B1: DIE || '.$this->arr['tmp']['pair']);

							elseif( (float) $this->arr['percent'] >= (float) ($this->PP_bot - $this->ALL_OPTION['NOTIFICATIONS_DIFFERENCE']) ): //POWIADOMIENIE - mniejsze jak min percent a brakuje np 0.25%
								$values[] = $this->values(1);
								if( $this->ALL_OPTION['NOTIFICATIONS_TRANSACTION'] && ( (float) $this->ALL_OPTION['NOTIFICATIONS_TRANSACTION_SLEEP'] < time()) ):
									$this->notification();
								endif;
								$c = '1';

							elseif( (float) $this->arr['percent'] >= -1): // Dodajemy tylko do SQL
								$values[] = $this->values(0);
								//$this->notification(); 
								$c = '0';

							endif;
							$this->set_duplicate(180);

							$out[$this->arr['tmp']['pair']] = $c.' | RP: '.$this->arr['percent'];
							//$out = $this->arr;

						endif;

					endforeach;

				else:
					$out['error'] = 'brak array';

				endif;
			endforeach;
			$this->set_SQL($values);


		else:
			$out['error'] = 'brak porównania';
		endif;
		return $out;
	}


	public function icon($in){ //['znt_d']
		if($in>=0):
			$up_down = "\xF0\x9F\x93\x88";
			if($in<1): $up_number = "0⃣"; 
			elseif($in<5): $up_number = "1⃣"; 
			elseif($in<10): $up_number = "2⃣"; 
			elseif($in<30): $up_number = "3⃣"; 
			elseif($in<50): $up_number = "4⃣"; 
			elseif($in<100): $up_number = "5⃣"; 
			elseif($in<200): $up_number = "6⃣"; 
			elseif($in<300): $up_number = "7⃣"; 
			elseif($in<500): $up_number = "8⃣"; 
			elseif($in<1000): $up_number = "9⃣"; 

			elseif($in>=1000): $up_number = "🔟"; //10
			endif;
		else:
			$up_down = "\xF0\x9F\x93\x89";
			if($in<=-1000): $up_number = "🔟"; //10
			elseif($in<-1000): $up_number = "9⃣"; //9
			elseif($in<-500): $up_number = "8⃣"; 
			elseif($in<-300): $up_number = "7⃣"; 
			elseif($in<-200): $up_number = "6⃣"; 
			elseif($in<-100): $up_number = "5⃣"; 
			elseif($in<-50): $up_number = "4⃣"; //7
			elseif($in<-30): $up_number = "3⃣"; //6
			elseif($in<-10): $up_number = "2⃣"; //5
			elseif($in<-5): $up_number = "1⃣";	 //2
			elseif($in<-0): $up_number = "0⃣"; //0
			endif;
		endif;
		$results['up_down'] = $up_down;
		$results['up_number'] = $up_number;		
				
		return $results;	
	}




}