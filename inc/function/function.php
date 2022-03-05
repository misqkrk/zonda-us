<?php
//require 'login.php';
//require 'time.php';
//require 'websocket.php';


function get_course(){ // przeniesc do class
	return Exchange::GetCourse();	
}
function slaap($in){
	return Time::slaap($in);
}

// function precision($market = 'PLN'){
// 	switch ($market) {
// 		case 'BTC':
// 			return 8;
// 			break;
// 		case 'ETH':
// 			return 8;
// 			break;
// 		case 'USDT':
// 			return 6;
// 			break;	
// 		default:
// 			return 2;
// 			break;
// 	}
// }

function precision($cw = null){
	return match ($cw) {
		'PLN', 'USD', 'EUR', 'GBP' => 2,
		'USDC', 'USDT', 'XTZ' => 6,
		'EOS' => 4,
		default => 8,
	};
}

function cut_numberV2($amount = 0, $cw = null){
	$precision = precision($cw);

 	$amount = explode('.', $amount);
 	$amount[1] = ($amount[1] ? substr($amount[1], 0, $precision) : 0)   ;
 	$amount = implode('.', $amount);

 	return (float) $amount;
}


function cut_number($liczba, $precision = null, $waluta = null){
	if($precision == null):
 		if($waluta == 'BTC'):
 			$precision = 8;
 		else:
 			$precision = 2;
 		endif;
 	endif;

 	$liczba = explode('.', $liczba);
 	$liczba[1] = ($liczba[1] ? substr($liczba[1], 0, $precision) : 0)   ;
 	$liczba = implode('.', $liczba);

 	return (float) $liczba;
}


function html_error_html($text){
  return '

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
        <div class="notice error">'.$text.'</div>
				</div>
		</div>
	</div>
</div>';
}

function GetUUID($data){ // przeniesc do class
   	assert(strlen($data) == 16);
   	$data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
   	$data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}	


function title_site($title = null){
	if($title):
		return $title.' | ';
	else:
		$ex_title = explode('/', $_GET['site']);
		if($ex_title[1]):
			$o = str_replace("-", " ", $ex_title[1]);
			return strtoupper($o).' | ';
		endif;
	
	endif;
}

function marketPair(){ 
	foreach ($in as $k => $v1):
		foreach ($v1 as $v2):
			$out[] = $k.'-'.$v2;
		endforeach;
	endforeach;
	return $out;
}

function prowizja(?float $kwota, ?string $dzialanie, ?float $prowizja = PROWIZJA, ?int $d = 8){ //dzialanie = + /-
	if($prowizja == 0):
		return $kwota;
	else:
		if($dzialanie == '+'):
			$out =  ($kwota / (100 - $prowizja ) ) * 100 ;
		elseif($dzialanie == '-') :
			$out = $kwota - ( ($kwota / 100 ) * $prowizja);
		endif;

		return (float) round($out,$d);
	endif;
}


function prowizjaV2($kwota, $dzialanie, $pair = null, $type = 'taker', $d = 8){ // nie zrobione!!
	$trading_config = get_sql_trading_config()[$pair];
	if($trading_config):
		if($type == 'maker'):
			$prowizja = $trading_config['buyMaker'] * 100;
		else:
			$prowizja = $trading_config['buyTaker'] * 100;
		endif;
	else:
		if($type == 'maker'):
			$prowizja = PROWIZJA_MAKER;
		else:
			$prowizja = PROWIZJA;
		endif;
	endif;

	if($prowizja == 0):
		return $kwota;
	else:
		if($dzialanie == '+'):
			$out = $kwota + ($kwota * $prowizja / 100);

		elseif($dzialanie == '-') :
			$out = $kwota - (($kwota / 100) * $prowizja);

		endif;

		return round($out,$d);
	endif;
}


function prowizja_cena_sprzedazy($cena_zakupu, $prowizja_zakup = PROWIZJA, $prowizja_sprzedaz = null){ // DO KOSZA?
	if(!$prowizja_sprzedaz): //jezeli nie ma drugiej prowizji obie będą jak pierwsza
 		$prowizja_sprzedaz = $prowizja_zakup;
 	endif;

 	$a = (float) $cena_zakupu / (1- ( (float) $prowizja_zakup / 100));
	$cena_do_sprzedazy = $a / (1 - ( (float) $prowizja_sprzedaz / 100));
	return (float) $cena_do_sprzedazy;
}

function roznica_ilosc($bid, $ask){
	if( (float) $bid < (float) $ask):
		$ilosc = prowizja( (float) $bid, '+');
		if((float) $ilosc > (float) $ask):
			$ilosc = (float) $ask;
		endif;
	else: // jezeli jest wiecej ktoś sprzedaje niż ktoś chce kupić
		$ilosc = (float) $ask;
	endif;

	return $ilosc;
}

function procent_z($liczba = 0, $z_liczby = 0, $precision = 2){
	if($liczba && $z_liczby):
		$sum = fdiv($liczba, $z_liczby) * 100;
		return round($sum, $precision);
	else:
		return 0;
	endif;
}

function procent_wzrost($x = 0, $y = 0, $precision = 8){
	if($x && $y):
		$sum = ($y - $x)/$x * 100;
		return round($sum, $precision);
	else:
		return 0;
	endif;
}

function my_number($in, $d_in = 2){
	return number_format( (float) $in, $d_in, '.', ' ');
}






function roznica_procent(?float $a = 0, ?float $b = 0, ?int $round = 2){
	if($a != 0 && $b != 0):
		return round((( $b - $a) / $a ) * 100, $round);
	else:
		return 0;
		//return 'n/a';
	endif;
}
function procent(float $liczba, float $procent, string $dzialanie = '+', ?int $round = 8){
	if($dzialanie == '+'):
		$out = $liczba + (( $procent / 100) * $liczba);
	elseif($dzialanie == '-'):
		$out = $liczba / (1 + ( $procent / 100));
	endif;
	
	return (float) round($out,$round);
}

function roznica_procent_tolarancja(?float $a = 0, ?float $b = 0, ?float $tolarancja = 1){
	if($a != 0 && $b != 0):

		$roznica_procent = abs((( $b - $a) / $a ) * 100);
		if($roznica_procent >= $tolarancja):
			return true;
		else:
			return false;
		endif;
	else:
		return false;
		//return 'n/a';
	endif;
}

function tolerance_plus_minus($amount = null, $amount2 = null, $tolerance = null, $round = 4, $send = true){ // PRICE1 / PRICE2 // TOLERANCE
	$input = [
		'amount' => $amount,
		'amount2' => $amount2,
		'tolerance' => $tolerance,
		'round' => $round,
	];

	if($amount && $amount2 && $tolerance):

		$t['up'] = $up = round( (float) $amount + (( (float) $tolerance / 100) * (float) $amount), $round);
		$t['down'] = $down = round( (float) $amount / (1 + ( (float) $tolerance / 100)), $round);

		if( ($amount2 >= $down) && ($amount2 <= $up) ):
			$out['status'] = 'ok';
			$out['is_ok'] = true;

			$out['info'] = 'in range';

		elseif($amount2 < $down):
			$out['status'] = 'error';
			$out['info'] = 'below range';

		elseif($amount2 > $up):
			$out['status'] = 'error';
			$out['info'] = 'above range';

		else:
			$out['status'] = 'error';
			$out['info'] = 'unknown error';

		endif;

	else:
		$out['status'] = 'error';
		$out['error'] = 'missing amount or amount2 or tolerance';

	endif;

		$out['input'] = $input;
		$out['tolerance'] = $t;
		
		if($send && ($out['status'] == 'error') ):
			$send_error = '⛔ ERROR TOLERANCE'. PHP_EOL;
			send_telegram($send_error.print_r($out, true), true);
		endif;
	
	return $out;
}



function get_check_is_order_active($in = null, $course = null){
	if($in && in_array($course, $in)):
		return true;
	endif;
}

function html_notifi($msg = null, $type = 'green', $time = 6000, $audio= 'bling2' ){
	$out = '<script>
	$(document).ready(function() {
		mNotice.view({
			type: \''.$type.'\',
			message: \''.htmlspecialchars($msg, ENT_QUOTES).'\',
			audio: \''.$audio.'\',
			autoClose: '.$time.'
		});
	});
	</script>';

	return $out;
}

function add_notifi($msg = null, $type = 'green', $time = 6000, $audio= 'bling2' ){
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
	$add_sql = [
		'type' => $type,
		'audio' => $audio,
		'message' => htmlspecialchars($msg, ENT_QUOTES),
		'autoClose' => $time,
	];
	$DB->insertOne('notifications', $add_sql);
}

function add_errors($error = null, $type = 'bitbay', $info = null, $mode = null){
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
	$add_sql = [
		'type' => $type,
		'mode' => $mode,
		'error' => $error,
		'info' => $info,
	];
	$DB->insertOne('api_errors', $add_sql);
	return true;
}

function add_error($type = 'bitbay', $mode = null, $error = null,  $info = null ){ // V2 - new
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
	$add_sql = [
		'type' => $type,
		'mode' => $mode,
		'error' => $error,
		'info' => serialize($info),
	];
	$DB->insertOne('api_errors', $add_sql);	
	return true;
}

function search_in_redis($in, $get = false, $json_decode = false){
	$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
	
	$tmpKeysChennel = $redis->keys($in.'*');

	if($get):
		if($tmpKeysChennel):
			foreach ( $redis->getMultiple($tmpKeysChennel) as $k => $v):
				if($json_decode):
					$out[$tmpKeysChennel[$k]] = igbinary_unserialize($v);
				else:
					$out[$tmpKeysChennel[$k]] = igbinary_unserialize($v);
				endif;
			endforeach;
			return $out;
		endif;
	else:
		return $tmpKeysChennel;
	endif;
}

function search_in_redisV2($in, $get = false, $json_decode = true){
	$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
	
	$tmpKeysChennel = $redis->keys($in.'*');

	if($get):
		if($tmpKeysChennel):
			foreach ( $redis->getMultiple($tmpKeysChennel) as $k => $v):
				if($json_decode):
					$out[ cut_redis_key($tmpKeysChennel[$k]) ] = igbinary_unserialize($v);
				else:
					//$out[$tmpKeysChennel[$k]] = igbinary_unserialize($v);
				endif;
			endforeach;
			return $out;
		endif;
	else:
		return $tmpKeysChennel;
	endif;
}

function cut_redis_key($str){
	preg_match('/:(?<end>.[^:]+)$/is', $str, $match);
	return $match['end'];
}


function myHas($stringData, $flag){
	$key = 'hsjyqpdyakdjybcu';
	$cipher = "AES-128-CBC";
 	$iv = '1234567890123456';

	if($flag == 'encode'):
		return  urlencode(base64_encode(openssl_encrypt($stringData, $cipher, $key, $options=0, $iv)));
	else:
  		return openssl_decrypt(base64_decode(urldecode($stringData)), $cipher, $key, $options=0, $iv);
  endif;
}

function removeEmptyValuesAndSubarrays($array){
   foreach($array as $k=>&$v){
        if(is_array($v)){
            $v = removeEmptyValuesAndSubarrays($v);  // filter subarray and update array
            if(!count($v)){ // check array count
                unset($array[$k]);
            }
        }elseif(!strlen($v)){  // this will handle (int) type values correctly
            unset($array[$k]);
        }
   }
   return $array;
} 


function info_items_B2_buy($sql = []){
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

	$results = $DB->fetchAll($sql, null, null); 
	foreach ($results as $k => $v):
		$b = $DB->fetchAll("SELECT * FROM `BB_transactions` WHERE `offerId` = '".$v['id']."' ");
		$b_price_pln = null;
		$b_price_pln_sum = null;
		foreach ($b as $bk => $bv):
			$b_price_pln = courseTo(($bv['rate'] * $bv['amount']),$v['B_market']);
			$b_price_pln_sum += $b_price_pln;
		endforeach;

		$s = $DB->fetchAll("SELECT S.*, B.cw, B.S_market FROM B2_buy AS B JOIN B2_sell AS S ON B.id = S.buy_id WHERE S.buy_id = '".$v['id']."' ");
		$s_price_pln = null;
		$s_price_pln_sum = null;
		$s_price_pln_market = null;
		$s_price_pln_sum_market = null;

		foreach ($s as $sk => $sv):
			$s_price_pln = courseTo($sv['sell_price'],$sv['S_market']);
			$s_price_pln_sum += $s_price_pln;

			$s_price_pln_market = courseTo($sv['sell_market_price'],$sv['S_market']);
			$s_price_pln_sum_market += $s_price_pln_market;
		endforeach;

		$bs__profit = round( prowizja($s_price_pln_sum,'-') + prowizja($s_price_pln_sum_market,'-',PROWIZJA_MAKER) - $b_price_pln_sum  ,2);
		$profit_sum = $profit_sum + $bs__profit;

		$arr['items'][] = [
			'id' => $v['id'],
			'my_id' => $v['my_id'],
			'cw' => $v['cw'],
			'B_market' => $v['B_market'],
			'S_market' => $v['S_market'],
			'B_code' => $v['B_code'],
			't_code' => $v['t_code'],
			'max_percent' => number_format($v['max_percent'],2,'.','').' %',
			'dp' => number_format($v['dp'],2,'.','').' %',
			'B_price' => number_format( courseTo(($v['B_rate'] * $v['B_amount']),$v['B_market'])  ,2,'.','').' <span class="szary">PLN</span>', 
			'B_rate' => number_format($v['B_rate'],2,'.','').' '.$v['B_market'],
			'B_amount' => number_format($v['B_amount'],8,'.','').' <span class="szary">'.$v['cw'].'</span>',
			'receivedAmount' => ($v['receivedAmount'] ? $v['receivedAmount'].' <span class="szary">'.$v['cw'].'</span>' : null),
			'getSellAmount' => ($v['getSellAmount'] ? $v['getSellAmount'].' <span class="szary">'.$v['cw'].'</span>' : null),
			'date_start' => date("m-d H:i:s",strtotime($v['date_start'])),
			'date_stop' => date("m-d H:i:s",strtotime($v['date_stop'])),
			'open_time' => Time::seconds_to_min(Time::time_to_unixtimestamp($v['date_stop'])-Time::time_to_unixtimestamp($v['date_start'])),
			'status' => $v['code'],
			'link_item' => '/private/b2/detail-item/-/'.$v['my_id'],
			'b__amount' => '',
			'b__price_pln' => round($b_price_pln_sum,2).' <span class="szary">PLN</span>',
			's__price_pln' => round( prowizja($s_price_pln_sum,'-') + prowizja($s_price_pln_sum_market,'-',PROWIZJA_MAKER) ,2).' <span class="szary">PLN</span>',
			'bs__profit' => $bs__profit.' PLN',
		]; 

	endforeach;
	$arr['sum'] = [
		'profit_all' => $profit_sum,
	];
	return $arr;
}

function info_items_B3_sell($sql = []){
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

	$results = $DB->fetchAll($sql, null, null); 
	foreach ($results as $k => $v):
		$s = $DB->fetchAll("SELECT * FROM `BB_transactions` WHERE `offerId` = '".$v['id']."' ");
		$s_price_pln = null;
		$s_price_pln_sum = null;
		foreach ($s as $sk => $sv):
			$s_price_pln = courseTo(($sv['rate'] * $sv['amount']),$v['S_market']);
               
         $res_course = $DB->fetchAll("SELECT *  FROM `CURRENCY_history` WHERE `data` < '".Time::timeMs($sv['time'])."' ORDER BY `data` DESC LIMIT 3", null, null, 60)[0]; 
         $f_rate = $res_course[$v['S_market']];
			$s_price_pln = ($sv['rate'] * $sv['amount']) * $f_rate;

			$s_price_pln_sum += $s_price_pln;
		endforeach;

		$b = $DB->fetchAll("SELECT B.*, S.cw, S.B_market FROM B3_sell AS S JOIN B3_buy AS B ON S.id = B.sell_id WHERE B.sell_id = '".$v['id']."' ");
		$b_price_pln = null;
		$b_price_pln_sum = null;
		$b_price_pln_market = null;
		$b_price_pln_sum_market = null;

		foreach ($b as $bk => $bv):
			$b_price_pln = courseTo($bv['buy_price'],$bv['B_market']);
			$b_price_pln_sum += $b_price_pln;

			$b_price_pln_market = courseTo($bv['buy_market_price'],$bv['B_market']);
			$b_price_pln_sum_market += $b_price_pln_market;
		endforeach;

		 $bs__profit = round( 
		 	$s_price_pln_sum - (prowizja($b_price_pln_sum,'-') + prowizja($b_price_pln_sum_market,'-', PROWIZJA_MAKER) )   
		 	,2);

		//$bs__profit = $s_price_pln_sum;

		$profit_sum = $profit_sum + $bs__profit;
		$sell_sum = $sell_sum + $s_price_pln_sum;
		$buy_sum = $buy_sum + round( prowizja($b_price_pln_sum,'-') + prowizja($b_price_pln_sum_market,'-',PROWIZJA_MAKER) ,2);



		$arr['items'][] = [
			'id' => $v['id'],
			'my_id' => $v['my_id'],
			'cw' => $v['cw'],
			'B_market' => $v['B_market'],
			'S_market' => $v['S_market'],
			'S_code' => $v['S_code'],
			't_code' => $v['t_code'],
			'bam_actual' => $v['bam_actual'],
			'bam_new' => $v['bam_new'],
			'max_percent' => number_format($v['max_percent'],2,'.','').' %',
			'dp' => number_format($v['dp'],2,'.','').' %',
			'S_price' => my_number( courseTo(($v['S_rate'] * $v['S_amount']),$v['S_market'])  ).' <span class="szary">PLN</span>', 
			'S_rate' => my_number($v['S_rate']).' '.$v['S_market'],
			'S_amount' => number_format($v['S_amount'],8,'.','').' <span class="szary">'.$v['cw'].'</span>',
			'receivedAmount' => ($v['receivedAmount'] ? $v['receivedAmount'].' <span class="szary">'.$v['cw'].'</span>' : null),
			'getAmount' => ($v['getAmount'] ? $v['getAmount'].' <span class="szary">'.$v['cw'].'</span>' : null),
			'date_start' => date("d-m H:i:s",strtotime($v['date_start'])),
			'date_stop' => date("H:i:s",strtotime($v['date_stop'])),
			'open_time' => Time::seconds_to_min(Time::time_to_unixtimestamp($v['date_stop'])-Time::time_to_unixtimestamp($v['date_start'])),
			'status' => $v['code'],
			'link_item' => '/private/b3/detail-item/-/'.$v['my_id'],
			'b__amount' => '',
			's__price_pln' => $s_price_pln_sum,
			'b__price_pln' => prowizja($b_price_pln_sum,'-') + prowizja($b_price_pln_sum_market,'-',PROWIZJA_MAKER),
			'bs__profit' => $bs__profit.' PLN',
		]; 

	endforeach;
	$arr['sum'] = [
		'profit_all' => $profit_sum,
		'sell_all' => $sell_sum,
		'buy_all' => $buy_sum,

		
	];
	return $arr;
}

function icon_send_v2($in = 'info'){
	if($in == 10): $out = "💚";
	elseif($in == 12): $out = "💚";

	elseif($in == 11): $out = "🧡";
	elseif($in == 13): $out = "🤎";

	elseif($in == 30): $out = "❤️";	
	elseif($in == 31): $out = "❤️";	
	elseif($in == 32): $out = "❤️";	
	elseif($in == 33): $out = "❤️";	
	elseif($in == 34): $out = "❤️";	
	elseif($in == 35): $out = "❤️";
	elseif($in == 36): $out = "❤️";
	elseif($in == 37): $out = "❤️";
	elseif($in == 38): $out = "❤️";

	elseif($in == 20): $out = "🤍";	


	elseif($in == 'ok'): $out = "💚";
	elseif($in == 'notok'): $out = "💛";
	elseif($in == 'error'): $out = "❤";		
	elseif($in == 'info'): $out = "💙";
	elseif($in == 'fc'): $out = "💜";
	elseif($in == 'fc_wait'): $out = "💟";

	endif;
	return $out;
}





function sendIcon($in){ //['znt_d']
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



function binance_getAllowPair(){
	$in = Curl::single('https://api.binance.com/api/v1/exchangeInfo',true);
	if($in['symbols']):
		foreach ($in['symbols'] as $k => $v):
			if(in_array($v['baseAsset'], CW_LIST)):
				$out['items'][$v['symbol']] = [
					'cw' => $v['baseAsset'],
					'market' => $v['quoteAsset'],
				];
			endif;
		endforeach;
		
		return $out;
	endif;
}

function binance_cwAllowPair(){
	$in = binance_getAllowPair();
	if($in['items']):
		foreach ($in['items'] as $k => $v):
			$out['items'][$v['cw']][] = $v['market']; 
		endforeach;
		return $out;
	endif;

}


function add_to_child_array($arr = [], $add = []){
	if(is_array($arr)):
		foreach ($arr as $k => $v) {
			$out[$k] = $v;
		}
	endif;
	if(is_array($add)):
		foreach ($add as $k => $v) {
			$out[$k] = $v;
		}
	endif;
	return $out;
}




function cutNumber($number, $precision = 2){
	$tmp = (integer) ($number * 100);
	$number = $tmp / 100;
	return $number;
}

function cut_zero($in, $symbol = ''){
		$num = number_format( (float) $in, 8, '.', '');

		if( str_ends_with($num, '00000000') ):
			$precision = 2;
		elseif( str_ends_with($num, '0000000') ):
			$precision = 2;
		elseif( str_ends_with($num, '000000') ):
			$precision = 2;
		elseif( str_ends_with($num, '00000') ):
			$precision = 3;
		elseif( str_ends_with($num, '0000') ):
			$precision = 4;
		elseif( str_ends_with($num, '000') ):
			$precision = 5;
		elseif( str_ends_with($num, '00') ):
			$precision = 6;
		elseif( str_ends_with($num, '0') ):
			$precision = 7;
		else:
			$precision = 8;
		endif;
		return number_format($num, $precision, '.', $symbol);
	}








function login_acces($minimum_acces = 1){
	$user = $_SESSION[PROJECT]['AUTH'];
	if(!$user['userID'] || !$user['acces']):
		header('Location: https://'.$_SERVER['SERVER_NAME'].'/dashboard');
	endif;

	if($user['acces'] && $user['acces'] < $minimum_acces):
		echo 'brak dostępu';
		die();
	endif;
}
function acces($minimum_acces = 1){
	$user = $_SESSION[PROJECT]['AUTH'];

	if($user['acces'] && $user['acces'] < $minimum_acces):
		return false;
	else: 
		return true;
	endif;	
}

function login_logout(){
	unset($_SESSION[PROJECT]);
	header('Location: https://'.$_SERVER['SERVER_NAME'].'/dashboard');
}


?>