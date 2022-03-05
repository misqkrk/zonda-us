<?

/*
Public function:
buy
sell
marketBuy
marketSell
cancel
openOrders
tradingConfig
history
transactions
wallets
getHistoryFee(0, 200, '2017-12-11', '2017-12-12' )
*/



 class API{
	protected $api_key, $api_secret;

	public function __construct($bb_connect = BB_API) {
		//$this->api_key = BB_PUBLIC_30 ;
		//$this->api_secret = BB_PRIVATE_30;
		//$this->api_url = URL_API2;

		$this->api_key = $bb_connect['public'];
		$this->api_secret = $bb_connect['private'];
		$this->api_url = $bb_connect['host'];	
	}

	public function buy($symbol, $amount = null, $rate = null, $type = "limit", $flags = []) { // Symbol / Ilość / Kurs / Cena  ///strval
		return $this->order($symbol, 'BUY', $this->cut_zero($amount), $this->cut_zero($rate), $price,  $type, $flags);
	}

	public function sell($symbol, $amount = null, $rate = null, $type = "limit", $flags = []) { // Symbol / Ilość / Kurs / Cena
		return $this->order($symbol, 'SELL', $this->cut_zero($amount), $this->cut_zero($rate), $price, $type, $flags);
	}

	public function marketBuy($symbol = null, $amount = null, $price = null) {
		if($amount & $price) die("error: amount or price");
		return $this->order($symbol, 'BUY', $amount, null, $price, "market", $flags = []);
	}

	public function marketSell($symbol, $amount = null, $price = null) {
		if($amount & $price) die("error: amount or price");
		return $this->order($symbol, 'SELL', $amount, null, $price, "market", $flags = []);
	}

	public function cancel($orderid) {
		return $this->SignedCallApi("trading/offer/".$orderid,null,'DELETE');
	}

	public function cancelStop($orderid) {
		return $this->SignedCallApi("trading/stop/offer/".$orderid,null,'DELETE');
	}

	public function openOrders($symbol = null) { //BTC-USD itd.
		$get = $this->SignedCallApi("trading/offer".($symbol ? "/".$symbol : null) );
		if($get['items'])
		foreach ($get['items'] as $k => $v) :
			$ex = explode('-', $v['market']);
			$out[$v['id']] = $v;
			$out[$v['id']]['additional'] = $this->open_orders_additional($v);
		endforeach;

		return $this->items($get,$out);
	}

	public function openOrdersStop($symbol = null) { //BTC-USD itd.
		$get = $this->SignedCallApi("trading/stop/offer".($symbol ? "/".$symbol : null) );
		if($get['offers'])
		foreach ($get['offers'] as $k => $v) :
			$ex = explode('-', $v['market']);
			$out[$v['id']] = $v;
			$out[$v['id']]['additional'] = $this->open_orders_stop_additional($v);
		endforeach;
		//return $get;
		return $this->items($get,$out);
	}

	public function tradingConfig($symbol) { //BTC-USD itd.
		return $this->SignedCallApi("trading/config/".$symbol);
	}

	public function history($flags = []) {
		if(empty($flags['sort'])) $flags['sort'] = [["by" => "time", "order" => "DESC"]];
		if(empty($flags['limit'])) $flags['limit'] = strval(200);
		return $this->SignedCallApi("balances/BITBAY/history", $flags);
	}
	
	public function transactions($flags = []) {
		if(empty($flags['limit'])) $flags['limit'] = strval(300);
		return $this->SignedCallApi("trading/history/transactions", $flags);
	}

	public function wallets() {

		$res = $this->SignedCallApi("balances/BITBAY/balance");
		if($res['status'] == 'Ok'):
			foreach ($res['balances'] as $v):
				$b[$v['id']] = $v;
			endforeach;
			$out['status'] = $res['status'];
			$out['balances'] = $b;
			return $out;
		else:
			return $res;
		endif;
	}	

	public function order($symbol, $offerType, $amount = null, $rate = null, $price = null, $mode, $flags = []){
		$flags_add = $flags;
		$flags = [
			"amount" => $amount,
			"rate" => $rate,
			"offerType" => $offerType,
			"price" => $price,
			"mode" => $mode,
			
			"postOnly" => ( $flags['postOnly'] ? true : false),
			"fillOrKill" => ( $flags['fillOrKill'] ? true : false),
			"immediateOrCancel" => ( $flags['immediateOrCancel'] ? true : false),

			//"hidden" => (($flags['hidden'] == true) ? true: false),
			//"ocoValue" => (($flags['ocoValue'] == true) ? true : false),
			//"includeCommission" => true
		];
		$out = $this->SignedCallApi("trading/offer/".$symbol, $flags, "POST");
		//if($out) 
			$out['parm'] = array_merge($flags,$flags_add);
		return $out;
	}	

	public function openOrder($orderID) {
		$get = $this->openOrders();
		$out = array();
		if($get['items'])	
		foreach ($get['items'] as $v)
			if($v['id'] == $orderID) $out[] = $v;
	return $this->items($get,$out);
	}

	public function notifications($UUID, $flags= array()){
		$flags = [
			"size" => "30",
		];
		return $this->SignedCallApi("auth/".$UUID."/notifications?size=20");
	}

	//public function orderStatus($symbol, $orderid) {
	//	return $this->signedRequest("v3/order", ["symbol"=>$symbol, "orderId"=>$orderid]);
	//}

	public function SignedCallApi($method, $params = null, $type = 'GET', $value = 'query'){
		if ( empty($this->api_key) ) die("SignedCallApi error: API Key not set!");
		if ( empty($this->api_secret) ) die("SignedCallApi error: API Secret not set!");
	   $post = null;
	   
	   if( ($type == 'GET') && is_array($params) && isset($value) ):
	   	$method = $method.'?'.$value.'='.urlencode(json_encode($params));

	   elseif( ($type == 'GET') && is_array($params) && !isset($value) ):
	   	$method = $method.'?'.http_build_query($params);
	  	
	  	elseif( ($type == 'POST') && is_array($params) && (count($params) > 0) ):
	   	$post = json_encode($params);

	   endif;

	    $time = time();
	    $sign = hash_hmac("sha512", $this->api_key.$time.$post, $this->api_secret);
	    $operationId = $this->GetUUID(random_bytes(16));
	    $headers = array(
	        'API-Key: ' . $this->api_key,
	        'API-Hash: ' . $sign,
	        'operation-id: '.$operationId,
	        'Request-Timestamp: '.$time,
	        'Content-Type: application/json'
	    );
	 
	   $curl = curl_init();
	   curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	   curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
	   curl_setopt($curl, CURLOPT_HTTP_VERSION, 'CURL_HTTP_VERSION_1_1' ); //nowosc
	   curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	   curl_setopt($curl, CURLOPT_URL, 'https://'.$this->api_url.'/rest/'.$method);
	   if($type == 'POST'):
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		elseif($type == 'DELETE'):
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
	   endif;
	   //$info = curl_getinfo($curl);
	  //print_r($post);
	  //print_r($headers);


	   $cc = curl_exec($curl);
	   //print_r($cc);
		if($cc === false):
			$c_err = curl_error($curl);

				$arr_out['info'] = [
					'curl_error' => curl_error($curl),
			    	'operationId' => $operationId,
			    	'url' => $this->api_url,
			    	'method' => $method,
			    	'RequestTimestamp' => $time,
			    	'POST' => $post,
				];
				
			if(function_exists('add_error')):
				add_error('CURL', null, $c_err, $arr_out );
			endif;
		   return $arr_out;

		else:
			$arr_out = json_decode($cc, true);
			if($arr_out['status'] != 'Ok'):
				$arr_out['info'] = [
			    	'operationId' => $operationId,
			    	'url' => $this->api_url,
			    	'public_key' => $this->api_key,
			    	'method' => $method,
			    	'RequestTimestamp' => $time,
			    	'POST' => $post,
				];
				if(function_exists('add_error')):
					add_error('BITBAY', null, $arr_out['errors'][0], $arr_out );
				endif;

			endif;

		   return $arr_out;
		endif;
	}
























	private function cutTimestamp($time){
		return substr($time,0,-3);
	}
	private function dateMs($time){
		return date("Y-m-d H:i:s", substr($time,0,-3)).'.'.substr($time,-3);
	}
	private function GetUUID($data){
   	assert(strlen($data) == 16);
   	$data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
   	$data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}	

	private function items($in, $add){
		unset($in['items']);
		unset($in['offers']);

		$return = $in;
		$return['items'] = $add;
	return $return;
	}

	private function open_orders_stop_additional($v) { 
		$ex = explode('-', $v['market']);

			//$tmpMaxPrice = $v['rate'] * $v['startAmount']; //ile max wydam
			$is_reciveAmount = ( (float) $v['receivedAmount'] > 0 ? true : false );

			//$marketPrice = $v['lockedAmount']; // Cena realna ile zostało na giełdzie
			$marketPrice = $v['rate'] * $v['currentAmount']; // Cena realna ile zostało na giełdzie

			$instantAmount = round($v['startAmount'] - $v['currentAmount'] , 8);

			$tmpMaxMarketPrice = round($v['rate'] * $v['currentAmount'] , ($ex[1] != 'BTC' ? 2 : 8 ) ); //ile wtedy powinno zostać
			$tmpDifference = $marketPrice - $tmpMaxMarketPrice; //ile zostalo mi dodatkowej gotowki nie uzytej
			$tmpPriceInstant =  round($v['rate'] * $v['receivedAmount'] , ($ex[1] != 'BTC' ? 2 : 8 ) ); //Cena ile bym otrzymał po kursie takim jak zadany - nieprawidłowy czasem

			$instantPrice = ($is_reciveAmount ? round($tmpPriceInstant - $tmpDifference , ($ex[1] != 'BTC' ? 2 : 8) ) : 0); // Realna cena zakupu
			$instantRate = ($is_reciveAmount ? $instantPrice / $v['receivedAmount'] : 0); // Realny kurs zakupu natychmiastowego
				$out = [
					'cryptoCurrency' => $ex[0],
					'marketCurrency' => $ex[1],
					'createdAt' => $this->timeMs($v['createdAt']),
					'serverTime' => $this->timeMs(),
				];

		return $out;
	}


	private function open_orders_additional($v) { 
		$ex = explode('-', $v['market']);
		$tmp_market = $ex[1];

		if($v['offerType'] == 'Buy'):
			//$tmpMaxPrice = $v['rate'] * $v['startAmount']; //ile max wydam
			$is_reciveAmount = ( (float) $v['receivedAmount'] > 0 ? true : false );

			//$marketPrice = $v['lockedAmount']; // Cena realna ile zostało na giełdzie
			$marketPrice = $v['rate'] * $v['currentAmount']; // Cena realna ile zostało na giełdzie

			$instantAmount = round($v['startAmount'] - $v['currentAmount'] , 8);

			$tmpMaxMarketPrice = round($v['rate'] * $v['currentAmount'] , $this->precision($tmp_market) ); //ile wtedy powinno zostać
			$tmpDifference = $marketPrice - $tmpMaxMarketPrice; //ile zostalo mi dodatkowej gotowki nie uzytej
			$tmpPriceInstant =  round($v['rate'] * $v['receivedAmount'] , $this->precision($tmp_market) ); //Cena ile bym otrzymał po kursie takim jak zadany - nieprawidłowy czasem

			$instantPrice = ($is_reciveAmount ? round($tmpPriceInstant - $tmpDifference , $this->precision($tmp_market) ) : 0); // Realna cena zakupu
			$instantRate = ($is_reciveAmount ? $instantPrice / $v['receivedAmount'] : 0); // Realny kurs zakupu natychmiastowego
				$out = [
					'cryptoCurrency' => $ex[0],
					'marketCurrency' => $ex[1],
					'startPrice' => round($v['startAmount'] * $v['rate'] , $this->precision($tmp_market) ),
					'marketPrice' => round($marketPrice , $this->precision($tmp_market) ),
					'marketRate' => $v['rate'],
					'marketAmount' => $v['currentAmount'],
					'instantPrice' => $instantPrice,
					'instantRate' => round($instantRate , $this->precision($tmp_market) ) ,
					'instantAmount' => $instantAmount,
					'realizationAmount' => $instantAmount, //old do wywalenia potem
					'realizationPercent' => round( 100 - (($v['currentAmount'] / $v['startAmount']) * 100) , 2),
					'date' => date("Y-m-d H:i:s", $this->cutTimestamp($v['time'])),
					'dateMs' => $this->timeMs($v['time']),
					'serverTime' => $this->timeMs(),
				];

		elseif($v['offerType'] == 'Sell'):
				$is_reciveAmount = ( (float) $v['receivedAmount'] > 0 ? true : false );

				$startPrice = round($v['startAmount'] * $v['rate'] , $this->precision($tmp_market) );
				$marketPrice = round($v['currentAmount'] * $v['rate'] , $this->precision($tmp_market) ) ;
				$instantPrice = $startPrice - $marketPrice;
				$instantAmount = round($v['startAmount'] - $v['currentAmount'],8);
				$instantRate = ($is_reciveAmount ? round($instantPrice / $instantAmount , $this->precision($tmp_market) ) : 0);
				$out = [
					'cryptoCurrency' => $ex[0],
					'marketCurrency' => $ex[1],
					'startPrice' => $startPrice,
					'marketPrice' => $marketPrice,
					'marketRate' => $v['rate'],
					'marketAmount' => $v['currentAmount'],
					'instantPrice' => $instantPrice,
					'instantRate' => $instantRate,
					'instantAmount' => $instantAmount,
					'realizationAmount' => $instantAmount, //old do wywalenia potem
					'realizationPercent' => round( 100 - (($v['currentAmount']/$v['startAmount']) * 100) , 2),
					'date' => date("Y-m-d H:i:s", $this->cutTimestamp($v['time'])),
					'dateMs' => $this->timeMs($v['time']),
					'serverTime' => $this->timeMs(),
				];

		endif;


		return $out;
	}

////////////////////////////////////
	public function getHistoryFee($offset=0, $limit=200,  $fromTime=null, $toTime=null) {
		$flags = array(
      	"balanceCurrencies" => ["PLN", "EUR", "USD", "GBP"],
      	"fromTime"=> ($fromTime ? strval(strtotime($fromTime)."000"):null),
      	"toTime"=> ($toTime ? strval(strtotime($toTime)."000"):null),
      	"limit"=> strval($limit),
      	"offset"=> strval($offset),
      	"sort" => [
            ["by" => "time", "order" => "DESC"],
        	],
        	"types" => array("TRANSACTION_COMMISSION_OUTCOME",),
    	);
    	$get = $this->history($flags);
    	if($get['status']=='Ok')
	    	foreach ($get['items'] as $item):
	    		$out[] = [
	    			'historyId' => $item['historyId'],
	    			'currency' 	=> $item['balance']['currency'],
	    			'time'		=> $this->cutTimestamp($item['time']),
	    			'date'		=> date("Y-m-d H:i:s", $this->cutTimestamp($item['time'])),
	    			'value'		=> $item['value']
	    		];
	    	endforeach;
	return $this->items($get,$out);
	}
	
	public function getTransactionsHistory($nextPageCursor='start', $limit=300,  $fromTime = null, $toTime = null) {
		$currencyFiat = ['PLN', 'USD', 'EUR', 'GBP'];
		$flags = array(
	
      	//"fromTime"=> ($fromTime ? strval(strtotime("+1 hour",strtotime($fromTime))."000"):null),
      	//"toTime"=> ($toTime ? strval(strtotime("+1 hour",strtotime($toTime))."000"):null),
      	"fromTime"=> ($fromTime ? strval(strtotime($fromTime)."000"):null),
      	"toTime"=> ($toTime ? strval(strtotime($toTime)."000"):null),
      	//"toTime" => '1609542000000',
      	//"fromTime" => '1609455600000',
      	"limit"=> strval($limit),
      	"nextPageCursor"=> strval($nextPageCursor),
    	);
    	$get = $this->transactions($flags);
  		$i = 0;
    	if($get['status'] == 'Ok')
	    	foreach ($get['items'] as $item):
	    		$ex = explode("-", $item['market']);
				if (in_array($ex[1],$currencyFiat)): // pozostawienie wymian tylko na FIAT (arr)
					$out[] = [
						'ID' 			=> $item['id'],
						'crypto' 		=> $ex[0],
						'fiat'			=> $ex[1],
						'time'			=> $this->cutTimestamp($item['time']),
						'date'			=> date("Y-m-d H:i:s", $this->cutTimestamp($item['time'])),
						'userAction'	=> $item['userAction'],
						'amount'			=> $item['amount'],
						'rate'			=> $item['rate'],
						//'price'			=> ($item['userAction'] == "Buy" ? -abs(round($item['amount'] * $item['rate'], 4)) : round($item['amount'] * $item['rate'], 4) ),
						'price'			=> ($item['userAction'] == "Buy" ? -abs(round($item['amount'] * $item['rate'], 4)) : round($item['amount'] * $item['rate'], 4, PHP_ROUND_HALF_DOWN) ),

					];
				$i++;
				endif;
	    	endforeach;
	return $this->items($get,$out);
	}

	public function timeMs($in = null, $date = null){
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
	public function cut_zero($in){
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
		return number_format($num, $precision, '.', '');
	}


	public function precision($market = 'PLN'){
		switch ($market) {
			case 'BTC':
				return 8;
				break;
			case 'ETH':
				return 8;
				break;
			case 'USDT':
				return 6;
				break;	
			default:
				return 2;
				break;
		}
	}

}



//history:
 	//balancesId - tablica sald, których historia ma zostać zwrócona
	//balanceCurrencies - tablica kodów walut, które mają zostać zwrócone
	//fromTime - czas początkowy (UNIX timestamp)
	//toTime - czas końcowy (UNIX timestamp)
	//absValue - Wartość bezwzględna transakcji
	//fromValue - minimalna wartość, dla której wpisy mają zostać pobrane
	//toValue - maksymalna wartość, dla której wpisy mają zostać pobrane
	//sort - tablica sortowania. Sortowanie następuje kolejno według podanych parametrów. Każdy z elementów jest obiektem zawierającym parametry:
	//by - nazwa pola, według którego następuje sortowanie
	//order - kolejność sortowania (ASC / DESC)
	//limit - Maksymalna ilość wierszy pobrana w zapytaniu
	//offset - Ilość rekordów do pominięcia
	//types - Tablica typów operacji do zwrócenia

//transactions:
 	//markets - Tablica marketów, które mają zostać wyszukane
	//limit - Maksymalna ilość pobieranych wierszy - przesyłany jako string
	//offset - Ilość wierszy do opuszczenia - przesyłany jako string - zmienione
	//fromTime - Czas od którego mają zostać pobrane transakcje
	//toTime - Czas do którego mają zostać pobrane transakcje
	//initializedBy - Strona inicjująca, według której ma zostać przeprowadzone wyszukiwanie (Buy / Sell)
	//rateFrom - Minimalny kurs
	//rateTo - Maksymalny kurs
	//userAction - Akcja wykonana przez użytkownika odpytującego (Buy / Sell)
