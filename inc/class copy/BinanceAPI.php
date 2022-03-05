<?
/*
BYodM1yRVGU9Gd5VapzCklurI1ulhE7sAg3AxLFOrOsDvCUH0htrPig2V5tnj9PF

VDD02Z9qyPGfBTlVPFeNezIrMXR0jczu7abWuNnXXybVQIwL8wvyR06TScgNkaCi
*/


 class BinanceAPI {
 	
	protected $api_key;
	protected $api_secret;
 	protected $base = 'https://fapi.binance.com/'; 
  protected $stream = 'wss://fstream.binance.com/ws/'; 

	public function __construct($api_key = 'QnYhilPZ6woxJ57YNd38kxoUZfP6g3lzm4W1BlNKGHIe9tNySOfaCkswVzR4jaxa', $api_secret = 'aetb5KppDcPhargI7Wc3H9B2MNx2LA0war5foNkplWVgRYrGR1jRSIchOX7mKQSi') {
		$this->api_key = $api_key ;
		$this->api_secret = $api_secret;
		$this->api_url = $api_url;
	}

  public function useServerTime(){
      $request = $this->httpRequest("fapi/v1/time");
      if (isset($request['serverTime'])) {
          $this->info['timeOffset'] = $request['serverTime'] - (microtime(true) * 1000);
      }
  }

  public function exchangeInfo(){
      $request = $this->httpRequest("fapi/v1/exchangeInfo");
      return $request;

  }

  public function orderbook(string $symbol = null, int $limit = 10){
  	$options = [
  		'symbol' => $symbol,
  		'limit' => $limit,
  	];
  	if(isset($symbol)):
  		$request = $this->httpRequest("fapi/v1/depth", "GET", $options);
  		return $request;
 	 	endif;
  }

  public function recentTradeList(string $symbol = null, int $limit = 100,  array $params = []){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      print_r($options);
      if(isset($symbol)):
          $request = $this->httpRequest("fapi/v1/trades", "GET", $options);
          return $request;
      endif;
  }

  public function historicalTrades(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol)):
          $request = $this->httpRequest("fapi/v1/historicalTrades", "GET", $options, true);
          return $request;
      endif;
  }

  public function aggTrades(string $symbol = null, int $limit = 100){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      if(isset($symbol)):
          $request = $this->httpRequest("fapi/v1/aggTrades", "GET", $options);
          return $request;
      endif;
  }

  public function klines(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['interval'])):
          $request = $this->httpRequest("fapi/v1/klines", "GET", $options);
          return $request;
      endif;
  }

  public function continuousKlines(string $pair = null, int $limit = 100, array $params = []){
      $options = [
          'pair' => $pair,
          'limit' => $limit,
          'contractType' => 'PERPETUAL',
      ];
      $options = array_merge($options, $params);
      if(isset($pair) && isset($options['interval']) && isset($options['contractType'])):
          $request = $this->httpRequest("fapi/v1/continuousKlines", "GET", $options);
          return $request;
      endif;
  }   

  public function markPrice(string $symbol = null){
      $options = [
          'symbol' => $symbol,
      ];
      $request = $this->httpRequest("fapi/v1/premiumIndex", "GET", $options);
      return $request;
  }

  public function fundingRate(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/fundingRate", "GET", $options);
      return $request;
  }    

  public function ticker_24hr(string $symbol = null){
      $options = [
          'symbol' => $symbol,
      ];
      $request = $this->httpRequest("fapi/v1/ticker/24hr", "GET", $options);
      return $request;
  }  

  public function ticker_price(string $symbol = null){
      $options = [
          'symbol' => $symbol,
      ];
      $request = $this->httpRequest("fapi/v1/ticker/price", "GET", $options);
      return $request;
  }  

  public function ticker_bookTicker(string $symbol = null){
      $options = [
          'symbol' => $symbol,
      ];
      $request = $this->httpRequest("fapi/v1/ticker/bookTicker", "GET", $options);
      return $request;
  }  

  public function allForceOrders(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/allForceOrders", "GET", $options);
      return $request;
  }

  public function openInterest(string $symbol = null){
      $options = [
          'symbol' => $symbol,
      ];
      if(isset($symbol)):
          $request = $this->httpRequest("fapi/v1/openInterest", "GET", $options);
          return $request;
      endif;
  } 

  public function data_openInterestHist(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'period' => '5m',
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['period']) ):
          $request = $this->httpRequest("futures/data/openInterestHist", "GET", $options);
          return $request;
      endif;
  }  

  public function data_topLongShortAccountRatio(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'period' => '5m',
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['period']) ):
          $request = $this->httpRequest("futures/data/topLongShortAccountRatio", "GET", $options);
          return $request;
      endif;
  }  

  public function data_topLongShortPositionRatio(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'period' => '5m',
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['period']) ):
          $request = $this->httpRequest("futures/data/topLongShortPositionRatio", "GET", $options);
          return $request;
      endif;
  }  

  public function data_globalLongShortAccountRatio(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'period' => '5m',
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['period']) ):
          $request = $this->httpRequest("futures/data/globalLongShortAccountRatio", "GET", $options);
          return $request;
      endif;
  }  

  public function data_takerlongshortRatio(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'period' => '5m',
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['period']) ):
          $request = $this->httpRequest("futures/data/takerlongshortRatio", "GET", $options);
          return $request;
      endif;
  }

  public function lvtKlines(string $symbol = null, int $limit = 100, array $params = []){
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['interval']) ):
        $request = $this->httpRequest("fapi/v1/lvtKlines", "GET", $options);
      endif;
      return $request;
  }  

  public function indexInfo(string $symbol = null){
      $options = [
          'symbol' => $symbol,
      ];
      $request = $this->httpRequest("fapi/v1/indexInfo", "GET", $options);
      return $request;
  } 

// /////////////////////////////////////////// PRIVATE
  
  public function get_current_position() {
      $request = $this->httpRequest("fapi/v1/positionSide/dual", "GET", [], true);
      return $request;
  } 

  public function change_leverage(string $symbol = null, int $leverage = 1, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'leverage' => $leverage,
      ];

      if(isset($symbol) && isset($options['leverage']) ):
          $request = $this->httpRequest("fapi/v1/leverage", "POST", $options, true);
          return $request;
      endif;
  } 

  public function change_position_mode(string $dualSidePosition = null) {
      $options = [
          'dualSidePosition' => $dualSidePosition,
      ];
      if(isset($options['dualSidePosition'])):
        $request = $this->httpRequest("fapi/v1/positionSide/dual", "POST", $options, true);
      endif;
      return $request;
  } 

  public function get_position_mode() {
      $request = $this->httpRequest("fapi/v1/positionSide/dual", "GET", $options, true);
      return $request;
  } 

  public function post_order(string $symbol = null, $side, $type, $quantity, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'side' => $side,
          'type' => $type,
          'quantity' => $quantity,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($side) && isset($type)):
        $request = $this->httpRequest("fapi/v1/order", "POST", $options, true);
      endif;
      return $request;
  } 

  public function get_order(string $symbol = null, $orderId) {
      $options = [
          'symbol' => $symbol,
          'orderId' => $orderId,
      ];
      $request = $this->httpRequest("fapi/v1/order", "GET", $options, true);
      return $request;
  } 

  public function cancel_order(string $symbol = null, $orderId = null) {
      $options = [
          'symbol' => $symbol,
          'orderId' => $orderId,
      ];
      $request = $this->httpRequest("fapi/v1/order", "DELETE", $options, true);
      return $request;
  } 

  public function cancel_all_orders(string $symbol = null) {
      $options = [
          'symbol' => $symbol,
      ];
      $request = $this->httpRequest("fapi/v1/allOpenOrders", "DELETE", $options, true);
      return $request;
  } 

  public function cancel_list_orders(string $symbol = null, $orderIdList) {
      $options = [
          'symbol' => $symbol,
          'orderIdList' => $orderIdList,
      ];
      if(isset($$orderIdList) ) :
        $request = $this->httpRequest("fapi/v1/batchOrders", "DELETE", $options, true);
      endif;
      return $request;
  } 

  public function cancel_auto_all_orders(string $symbol = null, $countdownTime) {
      $options = [
          'symbol' => $symbol,
          'countdownTime' => $countdownTime,
      ];
      if(isset($symbol) && isset($options['countdownTime']) ):
        $request = $this->httpRequest("fapi/v1/allOpenOrders", "DELETE", $options, true);
      endif;
      return $request;
  } 

  public function get_open_order(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/openOrder", "GET", $options, true);
      return $request;
  } 

  public function get_open_orders(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/openOrders", "GET", $options, true);
      return $request;
  } 

  public function get_all_orders(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/allOrders", "GET", $options, true);
      return $request;
  }

  public function get_balance(string $recvWindow = null) {
      $options = [
          'recvWindow' => $recvWindow,
      ];
      $request = $this->httpRequest("fapi/v2/balance", "GET", $options, true);
      return $request;
  } 

  public function get_account_information(string $recvWindow = null) {
      $options = [
          'recvWindow' => $recvWindow,
      ];
      $request = $this->httpRequest("fapi/v2/account", "GET", $options, true);
      return $request;
  }  

  public function change_initial_leverage(string $symbol = null, int $leverage = 1, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'leverage' => $leverage,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['leverage']) ):
          $request = $this->httpRequest("fapi/v1/leverage", "POST", $options, true);
          return $request;
      endif;
  } 

  public function change_margin_type(string $symbol = null, string $marginType, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'marginType' => $marginType,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['leverage']) ):
          $request = $this->httpRequest("fapi/v1/marginType", "POST", $options, true);
          return $request;
      endif;
  } 

  public function change_position_margin(string $symbol = null, float $amount, int $type, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'amount' => $amount,
          'type' => $type,
      ];
      $options = array_merge($options, $params);
      if(isset($symbol) && isset($options['amount'])&& isset($options['type']) ):
          $request = $this->httpRequest("fapi/v1/positionMargin", "POST", $options, true);
          return $request;
      endif;
  } 

  public function get_position_margin_change_history(string $symbol = null, int $limit = 100, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/positionMargin/history", "GET", $options, true);
  } 

  public function get_position(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v2/positionRisk", "GET", $options, true);
      return $request;
  }  

  public function get_account_trades(string $symbol = null, int $limit = 100, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/userTrades", "GET", $options, true);
      return $request;
  } 

  public function get_income_history(string $symbol = null, int $limit = 100, array $params = []) {
      $options = [
          'symbol' => $symbol,
          'limit' => $limit,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/income", "GET", $options, true);
      return $request;
  }  

  public function get_leverage_bracket(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/leverageBracket", "GET", $options, true);
      return $request;
  }  

  public function get_adl_quantile(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/adlQuantile", "GET", $options, true);
      return $request;
  }  

  public function get_force_orders(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/forceOrders", "GET", $options, true);
      return $request;
  }  

  public function get_api_trading_stats(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/apiTradingStatus", "GET", $options, true);
      return $request;
  }  

  public function get_user_commission_rate(string $symbol = null, array $params = []) {
      $options = [
          'symbol' => $symbol,
      ];
      $options = array_merge($options, $params);
      $request = $this->httpRequest("fapi/v1/commissionRate", "GET", $options, true);
      return $request;
  }  

  public function start_user_data_stream() {
      $request = $this->httpRequest("fapi/v1/listenKey", "POST", [], true);
      return $request;
  }  

  public function keep_user_data_stream() {
      $request = $this->httpRequest("fapi/v1/listenKey", "PUT", [], true);
      return $request;
  }  

  public function close_user_data_stream() {
      $request = $this->httpRequest("fapi/v1/listenKey", "DELETE", [], true);
      return $request;
  }  


////













protected function httpRequest(string $url, string $method = "GET", array $params = [], bool $signed = false)
    {
        if(function_exists('curl_init') === false):
            throw new \Exception("Sorry cURL is not installed!");
        endif;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_VERBOSE, $this->httpDebug);
        $query = http_build_query($params, '', '&');

        if($signed === true): // signed with params
            if(empty($this->api_key)):
                throw new \Exception("signedRequest error: API Key not set!");
            endif;

            if(empty($this->api_secret)):
                throw new \Exception("signedRequest error: API Secret not set!");
            endif;

            $base = $this->base;
            $ts = (microtime(true) * 1000) + $this->info['timeOffset'];
            $params['timestamp'] = number_format($ts, 0, '.', '');
		
            $query = http_build_query($params, '', '&');
            $signature = hash_hmac('sha256', $query, $this->api_secret);
            if($method === "POST"):
                $endpoint = $base . $url;
				        $params['signature'] = $signature; // signature needs to be inside BODY
				        $query = http_build_query($params, '', '&'); // rebuilding query
            else:
                $endpoint = $base . $url . '?' . $query . '&signature=' . $signature;
            endif;

            curl_setopt($curl, CURLOPT_URL, $endpoint);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'X-MBX-APIKEY: ' . $this->api_key,
            ));
        
        elseif(count($params) > 0): // params so buildquery string and append to url
            curl_setopt($curl, CURLOPT_URL, $this->base . $url . '?' . $query);
        
        else:   // no params so just the base url
            curl_setopt($curl, CURLOPT_URL, $this->base . $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'X-MBX-APIKEY: ' . $this->api_key,
            ));

        endif;
        curl_setopt($curl, CURLOPT_USERAGENT, "User-Agent: Mozilla/4.0 (compatible; PHP Binance API)");

        if($method === "POST"):
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
        endif;

        if($method === "DELETE"):
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        endif;

        if($method === "PUT"):
            curl_setopt($curl, CURLOPT_PUT, true);
        endif;


        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);

        $output = curl_exec($curl);
        if(curl_errno($curl) > 0): // Check if any error occurred

            // should always output error, not only on httpdebug
            // not outputing errors, hides it from users and ends up with tickets on github
            throw new \Exception('Curl error: ' . curl_error($curl));
        endif;
    
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($output, 0, $header_size);
        $output = substr($output, $header_size);
        
        curl_close($curl);
        
        $json = json_decode($output, true);
        
        $this->lastRequest = [
            'url' => $url,
            'method' => $method,
            'params' => $params,
            'header' => $header,
            'json' => $json
        ];


        if(isset($json['msg'])):
            $json['error'] = true;
            return $json;
            //throw new \Exception('signedRequest error: '.print_r($output, true));
        endif;
        $this->transfered += strlen($output);
        $this->requestCount++;
        return $json;
    }


}