<?
class Modules {
	static function OpenOrders($CW = null){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

		if($CW): 

			$open = igbinary_unserialize($redis->get('BB:WS:PRIVATE:open_orders'));
			if($open['items']):
				foreach ($open['items'] as $item):
					$ex = explode('-',$item['market']);
					$items[$ex[0]][] = $item;
				endforeach; 
			endif;
			
			if($items[$CW]):    
				$out .= '<div class="box bg">';

				$out .= '<table>';
					$out .= '<tr class="nh">';
						$out .= '<th>TYPE</th>';
						$out .= '<th>RATE</th>';
						$out .= '<th>PROGRESS</th>';
						$out .= '<th>AMOUNT</th>';
						$out .= '<th>DATE</th>';

						$out .= '<th>C</th>';
					$out .= '</tr>';

				foreach ($items[$CW] as $k0 => $v0):
					$out .= '<tr>';
						$out .= '<td class="'.($v0['offerType'] == 'Sell' ? 'czerwony' : 'zielony').'">'.strtoupper($v0['offerType']).'</td>';
						$out .= '<td>'.$v0['rate'].' <span class="szary">'.$v0['additional']['marketCurrency'].'</span></td>';
						$out .= '<td>'.$v0['additional']['realizationPercent'].' <span class="szary">%</span></td>';
						$out .= '<td title="from: '.$v0['startAmount'].' '.$v0['additional']['cryptoCurrency'].'">'.($v0['startAmount'] - $v0['additional']['realizationAmount']).'</span></td>';
						$out .= '<td >'.$v0['additional']['dateMs'].'</td>';

						$out .= '<td>
							<form method="post" action="/ajax/post/cancel.php" target="modal">
			              <input name="confirm" value="true" type="hidden">
			              <input type="hidden" name="pair" value="'.$CW.'-'.$v0['additional']['marketCurrency'].'" >
			              <input type="hidden" name="uuid" value="'.$v0['id'].'" >
			              <input type="hidden" name="type" value="'.strtolower($v0['offerType']).'" >
			              <input type="hidden" name="rate" value="'.$v0['rate'].'" >
			              <input type="submit" class="send-1 min-1" value="x">
			            </form>
						</td>';
					$out .= '<tr>';
				endforeach;

				$out .= '</table>';
			
				$out .='</div>';

			endif;

		endif;

		return $out;
	}

	static function ManualBuySell(){

		$out .= '<div class="box bg">';

		$out .= '<table class="no-td">	
			<tr><th>Manual BUY / SELL </th></tr>
			<tr><td class="ta-l">	
				<div id="form_big">
					<form method="post" action="/ajax/post/modal.order.php" target="modal">
					<input name="confirm" value="true" type="hidden">

					<input type="hidden" name="my_info[type]" value="manual">
					<input type="hidden" name="my_info[mode]" value="www"> 

					<select name="type">
					    <option value="BUY">BUY</option>
					    <option value="SELL">SELL</option>
					</select>';

					$out .= '<select name="MANUAL[pair]">';
						foreach (CW_MARKET_LIST as $cw => $mm):
							if($_GET['get'] == $cw || !$_GET['get']):
								$out .= '<optgroup label="'.$cw.' ">';
								foreach ($mm as $market):
									if($cw != $market) $out .= '<option value="'.$cw.'-'.$market.'">'.$cw.'-'.$market.'</option>';
								endforeach;
								$out .= '</optgroup>';
							endif;
						endforeach;

					$out .= '</select>';

					$out .= '<br>
					
					<p><input  type="text" required pattern="[0-9.]+" name="MANUAL[amount]" placeholder="AMOUNT"></p>
					<p><input  type="text" required pattern="[0-9.]+" name="MANUAL[rate]" placeholder="RATE"></p>

					<label><input type="checkbox" name="MANUAL[immediateOrCancel]"checked value="1">IMMEDIATE OR CANCEL</label>
					<label><input type="checkbox" name="MANUAL[postOnly]" value="1">POST ONLY</label>
					<label><input type="checkbox" name="MANUAL[fillOrKill]" value="1">FILL OR KILL</label>

					<br>
					<input type="submit"  onclick="return confirm(\'Na pewno?\')"  value="BUY / SELL">

					</form>
				</div>';

			$out .= '</td></tr>
			</table>';
			

		$out .='</div>';
		
		return $out;
	}


	static function InfoWsStatus(){
		$o .= (search_in_redis('INFO:WS:STATUS:PublicTicker') ? null: "Ticker: \xF0\x9F\x94\xB4");
		$o .= (search_in_redis('INFO:WS:STATUS:PublicFiatCantor') ? null : "FiatCantor: \xF0\x9F\x94\xB4");
		$o .= (search_in_redis('INFO:WS:STATUS:PublicTransactions') ? null : "Transactions: \xF0\x9F\x94\xB4");
		$o .= (search_in_redis('INFO:WS:STATUS:PublicStats') ? null : "Stats: \xF0\x9F\x94\xB4");
		$o .= (search_in_redis('INFO:WS:STATUS:PrivateBalance') ? null : "PrivateBalance: \xF0\x9F\x94\xB4");
		$o .= (search_in_redis('INFO:WS:STATUS:PrivateTransactions') ? null : "PrivateTransactions: \xF0\x9F\x94\xB4");
		$o .= (search_in_redis('INFO:WS:STATUS:PrivateOpenOrders') ? null : "PrivateOpenOrders: \xF0\x9F\x94\xB4");

		if($o):
			$out .= '<div class="box bg padding-3">';
			$out .= '<div class="b-p"><div class="padding-3">INFO WS STATUS:</div></div>';

			$out .= '<div class="padding-3">'.$o.'</div>';
			$out .= '<div class="clearfix"></div>';
			$out .='</div>';
			
			return $out;
		else:
			return false;
		endif;
	}

	static function Exchange(){
		$course_o = $_ENV[PROJECT]['get_course'] ?? Exchange::GetCourse();
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

		$course = Exchange::GetCourseHistory();

		$currencycom = igbinary_unserialize($redis->get('CURRENCYCOM:WS:currency'));

		$out .= '<div class="box bg">';
			
			$out .= '<table class="no-td">';
				$out .= '<tr>';
					$out .= '<th class="ta-l">USD</th>';
					$out .= '<th class="ta-l">EUR</th>';
					$out .= '<th class="ta-l">GBP</th>';
					$out .= '<th class="ta-l">BTC</th>';
				$out .= '</tr>';
				$out .= '<tr>';
					$out .= '<td class="ta-l">now: '.$course['now']['USDPLN'].'<br>1h: '.Colors::PercentHtml($course['1h']['USD']).'<br>1d: '.Colors::PercentHtml($course['1d']['USD']).'</td>';
					$out .= '<td class="ta-l">now: '.$course['now']['EURPLN'].'<br>1h: '.Colors::PercentHtml($course['1h']['EUR']).'<br>1d: '.Colors::PercentHtml($course['1d']['EUR']).'</td>';
					$out .= '<td class="ta-l">now: '.$course['now']['GBPPLN'].'<br>1h: '.Colors::PercentHtml($course['1h']['GBP']).'<br>1d: '.Colors::PercentHtml($course['1d']['GBP']).'</td>';
					$out .= '<td class="ta-l">'.my_number($course_o['BTC']).' PLN</td>';
				$out .= '</tr>';

				$out .= '<tr>';
					$out .= '<td class="ta-l">forex: '.$currencycom['items']['USD-PLN']['avg'].'</td>';
					$out .= '<td class="ta-l">forex: '.$currencycom['items']['EUR-PLN']['avg'].'</td>';
					$out .= '<td class="ta-l">forex: '.$currencycom['items']['GBP-PLN']['avg'].'</td>';
					$out .= '<td class="ta-l">'.$currencycom['time'].'</td>';
				$out .= '</tr>';

			$out .= '</table>';
		

		$out .='</div>';
		
		return $out;
	}	

	static function CmcCoins(){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		
		$coinmarketcap = igbinary_unserialize( $redis->get('MAIN:coinmarketcap') ); 
		$c_1m = cmc_top('1', $coinmarketcap);
		$c_5m = cmc_top('5', $coinmarketcap);
		$c_1h = cmc_top('percent_change_1h', $coinmarketcap);
		$c_24h = cmc_top('percent_change_24h', $coinmarketcap);

		$out .= '<div class="box bg padding-3">';
			
			$out .= '<ul class="ul-up-down">';
				$out .= '<li class="th">1M</li>';
					if($c_1m['up']):
						foreach ($c_1m['up'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r zielony b-r"> '.$v.' % </div></li>';
						endforeach;
					endif;
				$out .= '<hr>';
					if($c_1m['down']):
						foreach ($c_1m['down'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r czerwony b-r"> '.$v.' % </div></li>';
						endforeach;
					endif;
			$out .= '</ul>';

			$out .= '<ul class="ul-up-down">';
				$out .= '<li class="th">5M</li>';
					if($c_5m['up']):
						foreach ($c_5m['up'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r zielony b-r"> '.$v.' % </div></li>';
						endforeach;
					endif;
				$out .= '<hr>';
					if($c_5m['down']):
						foreach ($c_5m['down'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r czerwony b-r"> '.$v.' % </div></li>';
						endforeach;
					endif;
			$out .= '</ul>';

			$out .= '<ul class="ul-up-down">';
				$out .= '<li class="th">1H</li>';
					if($c_1h['up']):
						foreach ($c_1h['up'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r zielony b-r"> '.$v.' % </div></li>';
						endforeach;
					endif;
				$out .= '<hr>';
					if($c_1h['down']):
						foreach ($c_1h['down'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r czerwony b-r"> '.$v.' % </div></li>';
						endforeach;
					endif;
			$out .= '</ul>';

			$out .= '<ul class="ul-up-down">';
				$out .= '<li class="th">24H</li>';
					if($c_24h['up']):
						foreach ($c_24h['up'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r zielony"> '.$v.' % </div></li>';
						endforeach;
					endif;
				$out .= '<hr>';
					if($c_24h['down']):
						foreach ($c_24h['down'] as $k => $v):
							$out .= ' <li><div class="l">'.$k.'</div> <div class="r czerwony"> '.$v.' % </div></li>';
						endforeach;
					endif;
			$out .= '</ul>';
		$out .= '<div class="clearfix"></div>';

		$out .='</div>';
		
		return $out;
	}

	static function CmcCharts(){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$coinmarketcap = igbinary_unserialize($redis->get('MAIN:coinmarketcap'));
			$CW = $_GET['get'];

		if($coinmarketcap[$CW]):
			$out .= '<div class="box bg padding-3">';
			$out .= '<div class="b-b ta-c">Coin Market Caps - 7 Days</div>';

			$out .= '<div class="padding-3"><img style="border: 1px solid #2e2e2e;filter: contrast(500%);" src="https://s3.coinmarketcap.com/generated/sparklines/web/7d/usd/'.$coinmarketcap[$CW]['id'].'.png"></div>';
			//$out .= '<div class="padding-3"><img style="border: 1px solid #2e2e2e;filter: contrast(500%);" src="https://s2.coinmarketcap.com/generated/sparklines/web/1d/usd/'.$coinmarketcap[$CW]['id'].'.png"></div>';
				
			$out .='</div>';
		endif;
		return $out;
	}



	static function B1Conf(){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();

		$bpp = bot_pp_V2();
		$get_choice_plan_orders = FC::get_choice_plan_orders();
		$out .= '<div class="box bg">';

		
			$out .= '<table>';

				$arr = [
					'PLN-USD',
					'PLN-EUR',
					'PLN-GBP',
					'PLN-USDT',
					'PLN-USDC',

					'USD-PLN',
					'USD-EUR',
					'USD-GBP',
					'USD-USDT',
					'USD-USDC',

					'EUR-PLN',
					'EUR-USD',
					'EUR-GBP',
					'EUR-USDT',
					'EUR-USDC',

					'GBP-PLN',
					'GBP-USD',
					'GBP-EUR',
					'GBP-USDT',
					'GBP-USDC',

					'USDT-PLN',
					'USDT-USD',
					'USDT-EUR',
					'USDT-GBP',
					'USDT-USDC',

					'USDC-PLN',
					'USDC-USD',
					'USDC-EUR',
					'USDC-GBP',
					'USDC-USDT',
				];


					$out .= '<tr class="nh">';
						$out .= '<th class="ta-l">PAIR</th>';
						$out .= '<th class="ta-r">FC</th>';
						$out .= '<th class="ta-r">FC REV</th>';
						$out .= '<th class="ta-r">PERCENT</th>';
						$out .= '<th class="ta-r">+/-</th>';
						$out .= '<th class="ta-r">MIN</th>';
						$out .= '<th class="ta-r">TYPE</th>';

						$out .= '<th class="ta-r">BOT.P</th>';
					$out .= '</tr>';

				foreach ($arr as $k => $v):
					$ex = explode('-', $v);
					$out .= '<tr>';
						$out .= '<td class="ta-l"><span class="send-3 min-1 cw-bg-'.$ex[0].'">'.$ex[0].'</span><span class="send-3 min-1 cw-bg-'.$ex[1].'">'.$ex[1].'</span></td>';
						$out .= '<td><span class="szary">'.my_number($bpp[$v]['fc']).' %</span></td>';
						$out .= '<td><span class="szary">'.my_number($bpp[$v]['fc_reverse']).' %</span></td>';
						$out .= '<td>'.my_number($bpp[$v]['percent']).' <span class="szary">%</span></td>';
						$out .= '<td>'.$bpp[$v]['html_plus_minus'].'</td>';
						$out .= '<td>'.$bpp[$v]['html_min'].'</td>';
						$out .= '<td>'.$bpp[$v]['bot_type'].'</td>';

						$out .= '<td title="MANUAL: '.$bpp[$v]['bot_manual'].'% | AUTO: '.$bpp[$v]['bot_auto'].'% | AUTO NIGHT: '.$bpp[$v]['bot_auto_night'].'%"><span class="zolty">'.$bpp[$v]['bot'].'</span> <span class="szary">%</span></td>';
					$out .= '</tr>';	
				endforeach;


					// $out .= '<tr>';
					// 	$out .= '<td colspan="8" class="ta-l">FIAT CANTOR STATUS: '.igbinary_unserialize($redis->get('BB:FC:cantor_status'))['html'].'</td>';
					// $out .= '</tr>';	
					// $out .= '<tr>';
					// 	$out .= '<td colspan="8" class="ta-l">PLAN ORDERS: '.$get_choice_plan_orders['active_plan_name'].' - '.$get_choice_plan_orders['active_plan_percent'].'% ['.$get_choice_plan_orders['automat'].']</td>';
					// $out .= '</tr>';


			$out .= '</table>';


				$out .= '<form class="ta-r" action="/ajax/post/modal.req.php" method="post"  target="modal">';
						$out .= '<input type="hidden" name="a" value="update_account">';
						$out .= '<input type="submit" class="send-1 ok margin-8" value="Update Balance">';
				$out .= '</form>';		



				$out .= '<div class="clearfix"></div>';
		$out .='</div>';
		
		return $out;
	}

	static function AccountBalance($CW = null){
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$course = $_ENV[PROJECT]['get_course'] ?? Exchange::GetCourse();
		$stan_konta = BALANCE::sql_stan_konta(); 
		$ticker = igbinary_unserialize($redis->get('BB:WS:PUBLIC:ticker'));

		$out .= '<div class="box bg">';

			$out .= '<table>';
				$out .= '<tr class="nh">';
					$out .= '<th class="ta-r">COINS</th>';
					$out .= '<th class="ta-r">AVAILABLE</th>';
					$out .= '<th class="ta-r">LOCKED</th>';
					$out .= '<th class="ta-r">SUMMARY</th>';
				$out .= '</tr>';

				if(!$CW):
					foreach (CW_LIST as $key => $value):
						$out .= '<tr>';

							$out .= '<td>';
								$out .= '<a href="public/cw/-/'.$value.'"><span class="send-3 min-1 cw-bg-'.$value.'">'.$value.'</span></a> ';

								if($value != 'USDT'):
									$out .= ($redis->exists('BB:WS:PUBLIC:ORDERBOOK:'.$value ) ? null : " \xF0\x9F\x94\xB4");
									$out .= (search_in_redis('INFO:WS:REDIS:'.$value) ? null : "\xF0\x9F\x94\xB4");
								endif;

							$out .= '</td>';

							$out .= '<td><b class="'.($stan_konta[$value.'_A'] > 0? 'zolty': '').'" >'.$stan_konta[$value.'_A'].'</b></td>';
							$out .= '<td><b class="'.($stan_konta[$value.'_L'] > 0? 'zolty': '').'" >'.$stan_konta[$value.'_L'].'</b></td>';
							
							$out .= '<td>';		


								if($ticker['items'][$value.'-PLN']['rate'] ):
									$sum = ($stan_konta[$value.'_A'] + $stan_konta[$value.'_L']) * $ticker['items'][$value.'-PLN']['rate'];	
									
									$out .= '~ '.my_number($sum).'<span class="szary"> PLN</span>';
								endif;

							$out .= '</td>';

						$out .= '</tr>';
					endforeach;

				else:
					$value = $CW;
					$out .= '<tr>';

						$out .= '<td>';
							$out .= ( $redis->exists('BB:WS:PUBLIC:ORDERBOOK:'.$value)  ? null : " \xF0\x9F\x94\xB4");
							$out .= '<span class="send-3 min-1 cw-bg-'.$value.'">'.$value.'</span> ';
							$out .= (search_in_redis('INFO:WS:REDIS:'.$value) ? null : "\xF0\x9F\x94\xB4");
						$out .= '</td>';

						$out .= '<td><b class="'.($stan_konta[$value.'_A'] > 0? 'zolty': '').'" >'.$stan_konta[$value.'_A'].'</b></td>';
						$out .= '<td><b class="'.($stan_konta[$value.'_L'] > 0? 'zolty': '').'" >'.$stan_konta[$value.'_L'].'</b></td>';
						
						$out .= '<td>';		
							$in = igbinary_unserialize($redis->get('BB:WS:PUBLIC:ORDERBOOK:'.$value) );
							if($in[$value.'-PLN']['Buy'] && $in[$value.'-PLN']['Sell']):
								$b = current($in[$value.'-PLN']['Buy'])['ra'];
								$s = current($in[$value.'-PLN']['Sell'])['ra'];
								$avg = ($b + $s) / 2;
								$sum = ($stan_konta[$value.'_A'] + $stan_konta[$value.'_L']) * $avg;	
									
								$out .= '~ '.my_number($sum).'<span class="szary"> PLN</span>';
							endif;
						$out .= '</td>';

					$out .= '</tr>';

				endif;


				$value = null;
				$ii = 0;
				foreach (STABLE_LIST as $key => $value):
					$out .= '<tr '.($ii == 0 ? 'class="b-t"': null ).'>';
						$out .= '<td><span class="send-3 min-1 cw-bg-'.$value.'">'.$value.'</span> ';
						$out .= '<td><b class="'.($stan_konta[$value.'_A'] > 0? 'zolty': '').'" >'.my_number($stan_konta[$value.'_A'],8).'</b></td>';
						$out .= '<td><b class="'.($stan_konta[$value.'_L'] > 0? 'zolty': '').'" >'.my_number($stan_konta[$value.'_L'],8).'</b></td>';

						$out .= '<td>~ '.my_number(courseTo($stan_konta[$value.'_A'], $value)).' <span class="szary"> PLN</span> ('.round($stan_konta[$value.'_P'],0).' %)</td>';
						//$tmp_charts[$value] = round($stan_konta[$value.'_P'],0);
					$out .= '</tr>';
					$ii++;
				endforeach;

				$value = null;
				$ii = 0;
				foreach (FIAT_LIST as $key => $value):
					$out .= '<tr '.($ii == 0 ? 'class="b-t"': null ).'>';
						$out .= '<td><span class="send-3 min-1 cw-bg-'.$value.'">'.$value.'</span> ';
						$out .= '<td><b class="'.($stan_konta[$value.'_A'] > 0? 'zolty': '').'" >'.my_number($stan_konta[$value.'_A']).'</b></td>';
						$out .= '<td><b class="'.($stan_konta[$value.'_L'] > 0? 'zolty': '').'" >'.my_number($stan_konta[$value.'_L']).'</b></td>';

						$out .= '<td>'.my_number($stan_konta[$value.'_S']).'<span class="szary"> PLN</span> ('.round($stan_konta[$value.'_P'],0).' %)</td>';
						//$tmp_charts[$value] = round($stan_konta[$value.'_P'],0);
					$out .= '</tr>';
					$ii++;
				endforeach;

				$ALL_S = $stan_konta['ALL_S'];
				$ALL_SA = $stan_konta['ALL_SA'];
				$out .= '<tr class="b-t">';
					$out .= '<td><span class="send-3 min-1">FIAT</span> </td>';
					$out .= '<td>'.my_number($ALL_S).'<span class="szary"> PLN</span></td>';
					$out .= '<td>'.my_number($ALL_SA - $ALL_S).'<span class="szary"> PLN</span></td>';

					$out .= '<td>'.my_number($ALL_SA).'<span class="szary"> PLN</span></td>';
				$out .= '</tr>';
			$out .= '</table>';

		

		$out .='</div>';
		
		return $out;
	}


	static function Stats(){
		$course = $_ENV[PROJECT]['get_course'] ?? Exchange::GetCourse();
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

		$out .= '<div class="box bg">';
		$out .= '<div class="menu_button_r">
            <a class="tablink active" onclick="openTab(event,\'tab-1\')">B1</a>  
            <a class="tablink" onclick="openTab(event,\'tab-2\')">B2</a>  
            <a class="tablink" onclick="openTab(event,\'tab-4\')">FC</a>  
        </div>';
      $out .= '<div class="clearfix"></div>';
		
		$out .= '<div id="tab-1" class="tabClass">';
			$out .= '<table class="table_2">';
				$out .= '<tr><th class="ta-l">DATE</th><th class="ta-r">AMOUNTS</th></tr>';
		      $out .= Modules::b1_history(1, 0, 'Today', true);
				$out .= Modules::b1_history(0, 1, 'Yesterday');
				$out .= Modules::b1_history(1, 6, 'Last 7 days', true);
				$out .= Modules::b1_history(6, 13, 'Previous week');
				$out .= Modules::b1_history(1, 30, 'Last month', true);
				$out .= Modules::b1_history(29, 59, 'Previous month');
			$out .= '</table>';
		$out .= '</div>';

		$out .= '<div id="tab-2" class="tabClass" style="display:none">';
			$out .= '<table class="table_2">';
				$out .= '<tr><th class="ta-l">DATE</th><th class="ta-r">AMOUNTS</th></tr>';
		      $out .= Modules::b2_history(1, 0, 'Today', true);
				$out .= Modules::b2_history(0, 1, 'Yesterday');
				$out .= Modules::b2_history(1, 6, 'Last 7 days', true);
				$out .= Modules::b2_history(6, 13, 'Previous week');
				$out .= Modules::b2_history(1, 30, 'Last month', true);
				$out .= Modules::b2_history(29, 59, 'Previous month');
			$out .= '</table>';
		$out .= '</div>';
		

		$res = $DB->fetchAll("SELECT *,SUM(`fee`) as sumaFEE, SUM(`amountGivenPLN`) as sumaAmountGivenPLN FROM `BB_fc_history` GROUP BY DATE(`date`) ORDER BY `BB_fc_history`.`date` DESC LIMIT 6"); 

		$out .= '<div id="tab-4" class="tabClass" style="display:none">';
			$out .= '<table class="table_2" >';
			$out .= '<tr><th class="ta-l">TIME</th><th class="ta-r">FEE [PLN]</th><th class="ta-r">FEE %</th></tr>';
			foreach ($res as $k => $v):
				$out .= '<tr>';
					$out .= '<td class="ta-l">';
						$out .= date('Y-m-d',time_to_unixtimestamp($v['date']));
					$out .= '</td>';
					$out .= '<td>';
						$out .= my_number($v['sumaFEE']).' <span class="szary">PLN</span>';
					$out .= '</td>';
					$out .= '<td>';
						$out .= my_number( ($v['sumaFEE'] / $v['sumaAmountGivenPLN']) * 100 ).' <span class="szary">%</span>';
					$out .= '</td>';
				$out .= '</tr>';


			endforeach;
			$out .= '</table>';
		$out .= '</div>';




		$out .='</div>';
		$out .= '
		<script>
		function openTab(evt, tabName) {
		  var i, x, tablinks;
		  x = document.getElementsByClassName("tabClass");
		  for (i = 0; i < x.length; i++) {
		    x[i].style.display = "none";
		  }
		  tablinks = document.getElementsByClassName("tablink");
		  for (i = 0; i < x.length; i++) {
		    tablinks[i].className = tablinks[i].className.replace(" active", "");
		  }
		  document.getElementById(tabName).style.display = "block";
		  evt.currentTarget.className += " active";
		}
		</script>';	
		return $out;
	}	

	static function b1_history($d1 = 0, $d2 = 7, $text = null, $z_dzis = false){ //private
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		// 0 1 - wczoraj
		// 0 2 przedwczoraj
		// 1 0 true - dzis
		if($z_dzis):
			$sql = "
				SELECT * FROM `B1_orders` 
				WHERE `type` 
					IN ('exchange') 
					AND `status_date` >= '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-$d2, date('Y')))."' 
					AND `status_date` < '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')+$d1, date('Y')))."'
				";
		else:
			$sql = "
				SELECT * FROM `B1_orders` 
				WHERE `type` 
					IN ('exchange') 
					AND `status_date` >= '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-$d2, date('Y')))."' 
					AND `status_date` < '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-$d1, date('Y')))."'
				";
		endif;
		$results_dzis = $DB->fetchAll($sql, null, 60); 
			foreach ($results_dzis as $k => $v):
				$zarobek = round( ($v['sc_pln']+$v['scg_pln']) - ($v['kc_pln']+$v['kcg_pln']),2);
				$zarobek_all = $zarobek + $zarobek_all;
			endforeach;
			
		return '<tr><td class="ta-l">'.$text.'</td> <td class="ta-r">'.my_number($zarobek_all).' <span class="szary">PLN</span></td></tr>';
	}

	static function b2_history($d1 = 0, $d2 = 7, $text = null, $z_dzis = false){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
		// 0 1 - wczoraj
		// 0 2 przedwczoraj
		// 1 0 true - dzis
		if($z_dzis):
			$sql = "
				SELECT * FROM `B2_buy` 
				WHERE `is_receivedAmount` 
					IN ('1') 
					AND `date_start` >= '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-$d2, date('Y')))."' 
					AND `date_start` < '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')+$d1, date('Y')))."'
				";
		else:
			$sql = "
				SELECT * FROM `B2_buy` 
				WHERE `is_receivedAmount` 
					IN ('1') 
					AND `date_start` >= '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-$d2, date('Y')))."' 
					AND `date_start` < '".date('Y-m-d', mktime(0, 0, 0, date('m')  , date('d')-$d1, date('Y')))."'
				";
		endif;
		$arr = info_items_B2_buy($sql);

			
		return '<tr><td class="ta-l">'.$text.'</td> <td class="ta-r">'.my_number($arr['sum']['profit_all']).' <span class="szary">PLN</span></td></tr>';
	}


}