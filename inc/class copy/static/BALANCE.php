<?
class BALANCE{

	static function get_ws($pair = null){
		$API = $_ENV[PROJECT]['API'] ?? Connect::API();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		
		if($pair):
			$getTradingConfig[$pair] = GET_TRADING_CONFIG[$pair];
		else:
			$getTradingConfig = GET_TRADING_CONFIG;
		endif;

		$results = igbinary_unserialize($redis->get('BB:WS:PRIVATE:balances'));

		$return['status'] = $results['status'];
		$return['errors'] = $results['errors'];
		$return['timestamp'] = $results['timestamp'];

		if($results['status'] == 'Ok'):
			foreach ($getTradingConfig as $v):
				$return['items'][$v['pair']] = $v;
				$return['items'][$v['pair']]['firstBalance'] = $results['balances'][$v['firstBalanceId']] ;
				$return['items'][$v['pair']]['secondBalance'] = $results['balances'][$v['secondBalanceId']] ;
			endforeach;
		endif;
		return $return;		
	}
	
	static function publish_ws(){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$getTradingConfig = GET_TRADING_CONFIG;

		$res = igbinary_unserialize($redis->get('BB:WS:PRIVATE:balances'));

		$return['status'] = $res['status'];
		$return['errors'] = $res['errors'];
		$return['timestamp'] = $res['timestamp'];
		$return['time'] = time();
		if($res['status'] == 'Ok'):
			foreach ($getTradingConfig as $v):
				$return['items'][$v['pair']] = $v;
				$return['items'][$v['pair']]['firstBalance'] = $res['balances'][$v['firstBalanceId']] ;
				$return['items'][$v['pair']]['secondBalance'] = $res['balances'][$v['secondBalanceId']] ;
			endforeach;
		endif;
	   $redis->publish('channel-balances', igbinary_serialize( $return ) ); 
		return $return;
	}

	static function get_sql($pair = null){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		if($pair): $getTradingConfig[$pair] = GET_TRADING_CONFIG[$pair];
		else: $getTradingConfig = GET_TRADING_CONFIG;
		endif;

		$res = $DB->fetchAll("SELECT * FROM BB_balances", null, 'id', 30, 'BB_balances');

		foreach ($getTradingConfig as $v):
			$return['items'][$v['pair']] = $v;
			$return['items'][$v['pair']]['firstBalance'] = $res[$v['firstBalanceId']] ;
			$return['items'][$v['pair']]['secondBalance'] = $res[$v['secondBalanceId']] ;
		endforeach;

		return $return;
	}

	static function set_sql(){

	}

	static function get_rest($pair = null){
		$API = $_ENV[PROJECT]['API'] ?? Connect::API();
		if($pair): $getTradingConfig[$pair] = GET_TRADING_CONFIG[$pair];
		else: $getTradingConfig = GET_TRADING_CONFIG;
		endif;

		$results = $API->wallets();

		$return['status'] = $results['status'];
		$return['errors'] = $results['errors'];
		if($results['status']=='Ok'):
			foreach ($getTradingConfig as $v):
				$return['items'][$v['pair']] = $v;
				$return['items'][$v['pair']]['firstBalance'] = $results['balances'][$v['firstBalanceId']] ;
				$return['items'][$v['pair']]['secondBalance'] = $results['balances'][$v['secondBalanceId']] ;
			endforeach;
		endif;
		return $return;
	}

	static function cron(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		$DB->execute("UPDATE cron SET next = '0' WHERE name = 'balances'");
	}


	static function sql_stan_konta(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		return $DB->fetchAll("SELECT * FROM account_balance ORDER BY `ID` DESC LIMIT 1", null, null, 30, 'account_balance')[0];
	}

	static function update_rest(){
		$API = $_ENV[PROJECT]['API'] ?? Connect::API();
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$course = get_course();
		$credit_PLN = get_all_option()['CREEDIT_PLN'];

		$req = $API->wallets();
		if($req['status'] == 'Ok'):
			foreach ( $req['balances'] as $v):
				$add_sql = [
					'id' => $v['id'],
					'availableFunds' => $v['availableFunds'],
					'lockedFunds' => $v['lockedFunds'],
					'currency' => $v['currency'],
					'type' => $v['type'],
					'name' => $v['name'],
				];
				$DB->insertOrUpdate('BB_balances', $add_sql);
			endforeach;

			////// wbicie do hostorii stanu konta
			foreach ($req['balances'] as $vv):
				$tmp[$vv['currency']][] = $vv;
			endforeach;

			foreach ($tmp as $key => $items):
				$availableFunds = 0;
				$lockedFunds = 0;
				foreach ($items as $item):
					$availableFunds += $item['availableFunds'];
					$lockedFunds += $item['lockedFunds'];
				endforeach;
				$out[$key.'_A'] += $availableFunds; //potrzebne do reszty obliczen
				$out[$key.'_L'] += $lockedFunds; //potrzebne do reszty obliczen

				$add_sql_cw[$key.'_A'] += $availableFunds;
			  	$add_sql_cw[$key.'_L'] += $lockedFunds;
			endforeach;


			foreach (CW_LIST as $k => $vvv):
			  	$add_sql_cw[$vvv.'_A'] = $out[$vvv.'_A'];
			  	$add_sql_cw[$vvv.'_L'] = $out[$vvv.'_L'];
			endforeach;

			///WALUTA - DOSTEPNE SRODKI NA PLN
			$out['PLN_S'] = courseTo($out['PLN_A'], 'PLN');
			$out['USD_S'] = round(courseTo($out['USD_A'], 'USD'),2);
			$out['EUR_S'] = round(courseTo($out['EUR_A'], 'EUR'),2);
			$out['GBP_S'] = round(courseTo($out['GBP_A'], 'GBP'),2);
			$out['USDC_S'] = round(courseTo($out['USDC_A'], 'USDC'),2);
			$out['USDT_S'] = round(courseTo($out['USDT_A'], 'USDT'),2);

			//SUMA WALUTY DOSTEPNE SRODKI + ZABLOKOWANE SRODKI
			$out['PLN_AL'] = $out['PLN_A'] + $out['PLN_L'];
			$out['USD_AL'] = $out['USD_A'] + $out['USD_L'];
			$out['EUR_AL'] = $out['EUR_A'] + $out['EUR_L'];
			$out['GBP_AL'] = $out['GBP_A'] + $out['GBP_L'];
			$out['USDC_AL'] = $out['USDC_A'] + $out['USDC_L']; 
			$out['USDT_AL'] = $out['USDT_A'] + $out['USDT_L'];


			///WALUTA - DOSTEPNE SRODKI + ZABLOKOWANE NA PLN
			$out['PLN_SA'] = courseTo($out['PLN_A'] + $out['PLN_L'], 'PLN');
			$out['USD_SA'] = round(courseTo($out['USD_A'] + $out['USD_L'], 'USD'),2);
			$out['EUR_SA'] = round(courseTo($out['EUR_A'] + $out['EUR_L'], 'EUR'),2);
			$out['GBP_SA'] = round(courseTo($out['GBP_A'] + $out['GBP_L'], 'GBP'),2);
			$out['USDC_SA'] = round(courseTo($out['USDC_A'] + $out['USDC_L'], 'USDC'),2);
			$out['USDT_SA'] = round(courseTo($out['USDT_A'] + $out['USDT_L'], 'USDT'),2);


			$out['ALL_S'] = round($out['PLN_S'] + $out['USD_S'] + $out['EUR_S'] + $out['GBP_S'] + $out['USDC_S'] + $out['USDT_S'],0);
			$out['ALL_SA'] = round($out['PLN_SA'] + $out['USD_SA'] + $out['EUR_SA'] + $out['GBP_SA'] + $out['USDC_SA'] + $out['USDT_SA'],0);

			$out['PLN_P'] = round(($out['PLN_S'] / $out['ALL_S']) * 100,2);
			$out['USD_P'] = round(($out['USD_S'] / $out['ALL_S']) * 100,2);
			$out['EUR_P'] = round(($out['EUR_S'] / $out['ALL_S']) * 100,2);
			$out['GBP_P'] = round(($out['GBP_S'] / $out['ALL_S']) * 100,2);
			$out['USDC_P'] = round(($out['USDC_S'] / $out['ALL_S']) * 100,2);
			$out['USDT_P'] = round(($out['USDT_S'] / $out['ALL_S']) * 100,2);


			$add_sql = [
				'USDC_AL' => $out['USDC_AL'],
				'USDC_SA' => $out['USDC_SA'],

				'USDT_AL' => $out['USDT_AL'],
				'USDT_SA' => $out['USDT_SA'],

				'PLN_A' => $out['PLN_A'],
				'PLN_L' => $out['PLN_L'],
				'PLN_AL' => $out['PLN_AL'],
				'PLN_SA' => $out['PLN_SA'],

				'USD_A' => $out['USD_A'],
				'USD_L' => $out['USD_L'],
				'USD_AL' => $out['USD_AL'],
				'USD_SA' => $out['USD_SA'],

				'EUR_A' => $out['EUR_A'],
				'EUR_L' => $out['EUR_L'],
				'EUR_AL' => $out['EUR_AL'],
				'EUR_SA' => $out['EUR_SA'],

				'GBP_A' => $out['GBP_A'],
				'GBP_L' => $out['GBP_L'],
				'GBP_AL' => $out['GBP_AL'],
				'GBP_SA' => $out['GBP_SA'],

			 	'PLN_S' => $out['PLN_S'],
				'USD_S' => $out['USD_S'],
				'EUR_S' => $out['EUR_S'],
				'GBP_S' => $out['GBP_S'],
				'USDC_S' => $out['USDC_S'],
				'USDT_S' => $out['USDT_S'],

				'ALL_S' => $out['ALL_S'],
				'ALL_SA' => $out['ALL_SA'],

				'PLN_P' => $out['PLN_P'],
				'USD_P' => $out['USD_P'],
				'EUR_P' => $out['EUR_P'],
				'GBP_P' => $out['GBP_P'],
				'USDC_P' => $out['USDC_P'],
				'USDT_P' => $out['USDT_P'],
				'CREDIT_PLN' => $credit_PLN,

				'last' => '++NOW()',
			];
			$add_sql = array_merge($add_sql_cw, $add_sql);
			$DB->insertOne('account_balance', $add_sql);


			$redis->delete('SQL:KEY:BB_balances'); //BB_balances
			$redis->delete('SQL:KEY:B1_settings'); // B1_settings
			$redis->delete('SQL:KEY:account_balance'); // historia_stan_konta
			set_ws_bot_pp(); //aktualizacja bot price percent dla ws
			BALANCE::publish_ws(); // aktu balance dla ws

		else:
			add_errors('BALANCE UPDATE: '.$req['errors'][0], 'bitbay', serialize($req) );
		endif;
		
		return $req;
	}



}