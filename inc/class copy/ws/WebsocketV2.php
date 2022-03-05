<?php
// limitRest
// limitWs
class WebsocketV2{
    protected $test_i;
    protected $test_sum;


    protected $ws_t;
    protected $ws_count;
    protected $ws_count_sum;   
    protected $orderbook = [];
    protected $balances = [];
    protected $offers = [];
    protected $cmc = [];
    protected $cmc_tmp = [];

    protected $orderbook_memcache = [];
    protected $orderbook_memcache_3 = [];

    protected $fiat_cantor = [];
    protected $ticker = [];
    protected $binance_ticker = [];

    protected $stats = [];
    protected $kline = [];

    protected $tmp_cw = [];
    protected $timer;



    protected $transactions = [];
    protected $transactionsV2 = [];

    protected $info = [];
    protected $DB;
    protected $redis;

    protected $status_PID;
    protected $ping_invertal = 30;
    protected $new_rest_orderbook_time = 14400;


    public function __construct($parm = []) {
        $this->parm = $parm;
        $this->DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
        $this->redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();


        $this->api_key = BB_API['public'];
        $this->api_secret = BB_API['private'];
        $this->url_api = BB_API['host'];
        $this->url_api2 = BB_API['host'];

        //$this->limit = ($parm['limit'] ? $parm['limit'] : 10);
    }
    public function setParm($arr=[]){
        if(!empty($arr)):
            foreach ($arr as $key => $value) {
                $this->parm[$key] = $value;
            }
        endif;
    }

    protected function url_websocket(){
        $url = $this->url_api.'/websocket'; /////// zmiany
        $this->info['channel'] = getmypid();
        return 'wss://'.$url.'/';//;
    }

    protected function GetUUID($data){
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); 
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); 
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }   
  
    protected function CourseToKey($in){
        if( (float) $in < 1000000000): // zabezpieczenie na max ilość znaków (anty debile) 1 000 000 000
            return (float) $in * 100000000;
        else:
            return (float) 1000000000 * 100000000;
        endif;
    }

    protected function randChr($in = 8){
        while ($i++ < $in):
            $out .= strtolower(chr(65 + rand(0, 25)));
        endwhile;
    return $out;
    }

    protected function dashMarket($attr){
        foreach ($attr as $cw => $markets):
            foreach ($markets as $v):
                $out[] = $cw.'-'.$v;
            endforeach;
        endforeach;
    return $out;    
    }

    protected function dashToNoDashMarket($in){
        return str_replace('-', '', $in);
    }

    private function open_orders_stop_additional($v) { 
        $ex = explode('-', $v['market']);

                $out = [
                    'cryptoCurrency' => $ex[0],
                    'marketCurrency' => $ex[1],
                    'createdAt' => $this->timeMs($v['createdAt']),
                    'serverTime' => $this->timeMs(),
                ];

        return $out;
    }


    protected function open_orders_additional($v) { 
        $ex = explode('-', $v['market']);

        if($v['offerType'] === 'Buy'):
            //$tmpMaxPrice = $v['rate'] * $v['startAmount']; //ile max wydam
            $is_reciveAmount = ( (float) $v['receivedAmount'] > 0 ? true : false );

            $marketPrice = $v['lockedAmount']; // Cena realna ile zostało na giełdzie
            $instantAmount = round($v['startAmount'] - $v['currentAmount'] , 8);

            $tmpMaxMarketPrice = round($v['rate'] * $v['currentAmount'] , ($ex[1] != 'BTC' ? 2 : 8 ) ); //ile wtedy powinno zostać
            $tmpDifference = $marketPrice - $tmpMaxMarketPrice; //ile zostalo mi dodatkowej gotowki nie uzytej
            $tmpPriceInstant =  round($v['rate'] * $v['receivedAmount'] , ($ex[1] != 'BTC' ? 2 : 8 ) ); //Cena ile bym otrzymał po kursie takim jak zadany - nieprawidłowy czasem

            $instantPrice = ($is_reciveAmount ? round($tmpPriceInstant - $tmpDifference , ($ex[1] != 'BTC' ? 2 : 8) ) : 0); // Realna cena zakupu
            $instantRate = ($is_reciveAmount ? $instantPrice / $v['receivedAmount'] : 0); // Realny kurs zakupu natychmiastowego
                $out = [
                    'cryptoCurrency' => $ex[0],
                    'marketCurrency' => $ex[1],
                    'startPrice' => round($v['startAmount'] * $v['rate'] , ($ex[1] != 'BTC' ? 2 : 8)),
                    'marketPrice' => round($marketPrice , ($ex[1] != 'BTC' ? 2 : 8)),
                    'marketRate' => $v['rate'],
                    'marketAmount' => $v['currentAmount'],
                    'instantPrice' => $instantPrice,
                    'instantRate' => round($instantRate , ($ex[1] != 'BTC' ? 2 : 8) ) ,
                    'instantAmount' => $instantAmount,
                    'realizationAmount' => $instantAmount, //old do wywalenia potem
                    'realizationPercent' => round( 100 - (($v['currentAmount'] / $v['startAmount']) * 100) , 2),
                    'date' => date("Y-m-d H:i:s", $this->cutTimestamp($v['time'])),
                    'dateMs' => $this->timeMs($v['time']),
                    'serverTime' => $this->timeMs(),
                ];

        elseif($v['offerType'] === 'Sell'):
                $is_reciveAmount = ( (float) $v['receivedAmount'] > 0 ? true : false );

                $startPrice = round($v['startAmount'] * $v['rate'] , ($ex[1] != 'BTC' ? 2 : 8) );
                $marketPrice = round($v['currentAmount'] * $v['rate'] , ($ex[1] != 'BTC' ? 2 : 8 ) ) ;
                $instantPrice = $startPrice - $marketPrice;
                $instantAmount = round($v['startAmount'] - $v['currentAmount'],8);
                $instantRate = ($is_reciveAmount ? round($instantPrice / $instantAmount , ($ex[1] != 'BTC' ? 2 : 8) ) : 0);
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
                    'realizationPercent' =>round( 100 - (($v['currentAmount']/$v['startAmount']) * 100) , 2),
                    'date' => date("Y-m-d H:i:s", $this->cutTimestamp($v['time'])),
                    'dateMs' => $this->timeMs($v['time']),
                    'serverTime' => $this->timeMs(),
                ];

        endif;


        return $out;
    }


    protected function subscribe($attr, $start = null){ //null potem do kosza
        $this->info['PID'] = getmypid();
        $this->info['parent_PID'] = posix_getppid();

        if($start):
            $this->info['start_subscribe'] = $this->timeMs(null,true);
        endif;
        
        if($attr):
            return json_encode($attr);
        endif;
    }

    protected function status_PID($method, $type = null, $attr = []){
        $this->status_PID['start_date'] = ($this->status_PID['start_date'] ? $this->status_PID['start_date'] : $this->timeMs() );
        $this->status_PID['PID'] = getmypid();
        $this->status_PID['parent_PID'] = posix_getppid();

        $this->status_PID['method'] = $method;
        if($type === 'last'):
            $this->status_PID['last_date'] = $this->timeMs(null, true);
        elseif($type === 'keep'):
             $this->status_PID['keep_date'] = $this->timeMs(null, true);
        endif;
        $this->status_PID['start_attr'] = ($this->status_PID['start_attr'] ? $this->status_PID['start_attr'] : $attr );
       
        $l = 'INFO:WS:STATUS:'.$method.'#'.getmypid();
        $this->redis->set($l, igbinary_serialize($this->status_PID), 62);

        return $link;
    }

    protected function replaceToJSON($msg){
        return json_decode($msg,true);
    }

    protected function MyMultiCurl($urls){
        $headers  = [
            'Accent: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pl,en-US;q=0.9,en;q=0.8,de;q=0.7',
            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'Keep-Alive: 115'
        ];
                            
        $agents  = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.68 Safari/537.36'
        ];

        foreach ($urls as $k => $v):
            $ch[$k] = curl_init($v);
            curl_setopt($ch[$k], CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch[$k], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$k], CURLOPT_ENCODING, 'gzip,deflate');
            curl_setopt($ch[$k], CURLOPT_AUTOREFERER, true);
            curl_setopt($ch[$k], CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch[$k], CURLOPT_TIMEOUT, 10);
        endforeach;

        $mh = curl_multi_init();

        foreach ($urls as $k => $v):
            curl_multi_add_handle($mh, $ch[$k]);
        endforeach;

        $running = null;
        do{
            curl_multi_exec($mh, $running);
        }while ($running);

        foreach ($urls as $k => $v):
            curl_multi_remove_handle($mh, $ch[$k]);
        endforeach;

        curl_multi_close($mh);
      
        foreach ($urls as $k => $v):
            $out[$k] = curl_multi_getcontent($ch[$k]);
        endforeach;

    return $out;
    }

    protected function slice($tmpArr, $limit = 10, $preserve_keys = true){
        if($tmpArr['Buy']) $out['Buy'] = array_slice($tmpArr['Buy'],0, $limit);
        if($tmpArr['Sell']) $out['Sell'] = array_slice($tmpArr['Sell'],0, $limit);
    return $out;
    }

    protected function sortAndSlice($tmpArr, $limit = 10, $preserve_keys = true){
        if($tmpArr['Sell'] && $tmpArr['Buy']):
            ksort($tmpArr['Sell']);
            krsort($tmpArr['Buy']);
            $out['Buy'] = array_slice($tmpArr['Buy'], 0,  $limit, $preserve_keys);
            $out['Sell'] = array_slice($tmpArr['Sell'], 0, $limit, $preserve_keys);
        else:
            if($tmpArr['Sell']):
                ksort($tmpArr['Sell']);
                $out['Sell'] = array_slice($tmpArr['Sell'], 0, $limit, $preserve_keys);
            endif;
            if($tmpArr['Buy']):
                krsort($tmpArr['Buy']);
                $out['Buy'] = array_slice($tmpArr['Buy'], 0,  $limit, $preserve_keys);
            endif;
        endif;
    return $out;
    }

    protected function sortAndSliceOne($tmpArr, $limit = 10){ //?? czy bedzie uzywane?
        if($tmpArr) krsort($tmpArr);
        $out = array_slice($tmpArr,0, $limit, true);
    return $out;
    }

    protected function sliceAndReverse($tmpArr, $limit = 10){
        if($tmpArr): 
            $tmpArr = array_reverse($tmpArr);
            $o1 = array_slice($tmpArr, 0, $limit);
            foreach ($o1 as $v):
                $o2[] = $v;
            endforeach;
            $out = array_reverse($o2);
        endif;
    return $out;
    }

    protected function timeMs($in = null, $date = null){
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

    protected function cutTimestamp($time){
        return substr($time,0,-3);
    }


    protected function add_log($text){
        error_log($this->timeMs()." | ".getmypid()." || ".$text, 3, DIR_LOG."ws/".get_class($this)."-".date("d-m-Y").".log");
    }

    protected function add_log_CW($text){
        error_log($this->timeMs()." | ".getmypid().' || '.$text, 3, DIR_LOG."cw/".$this->info['cw'].".log");
    }

    public function getPublicOrderbook($attr){ 
        foreach ($this->dashMarket($attr) as $v):
            $arr[$v] = 'https://'.$this->url_api2.'/rest/trading/orderbook/'.$v.'?limit=300';
        endforeach;
        $fake_results = $this->MyMultiCurl($arr); // dodane - bład BB pierwszy raz pobiera stare z cache !
        $results = $this->MyMultiCurl($arr);

        if($results)
        foreach ($results as $k => $value):
            $d = json_decode($value, true);
            if($d['status'] == 'Ok'):
                foreach ($d['buy'] as $bid):
                    $out[$k]['Buy'][$this->CourseToKey($bid['ra'])] = [
                        'ra' => $bid['ra'],
                        'ca' => $bid['ca'],
                        'co' => $bid['co'],
                        't' => $d['timestamp'],
                    ];
                endforeach;

                foreach ($d['sell'] as $ask):
                    if( (float) $ask['ra'] < 1000000000):
                        $out[$k]['Sell'][$this->CourseToKey($ask['ra'])] = [
                        'ra' => $ask['ra'],
                        'ca' => $ask['ca'],
                        'co' => $ask['co'],
                        't' => $d['timestamp'],
                    ];
                    endif;
                endforeach;
                $out[$k]['info']['pair'] = $v;
            endif;

            if($this->parm['limitRest']) $out[$k] = $this->slice($out[$k], $this->parm['limitRest']);
        endforeach;
    return $out;
    }

    public function getPublicTransactions($attr){ 
        foreach ($this->dashMarket($attr) as $v):
            $arr[$v] = 'https://'.$this->url_api2.'/rest/trading/transactions/'.$v.'?limit=15';
        endforeach;
        
        $results = $this->MyMultiCurl($arr);

        if($results)
        foreach ($results as $k => $value):
            $d = json_decode($value, true);

            foreach ($d['items'] as $transaction):
                $out[$k][$transaction['id']] = [
                    't' => $transaction['t'],
                    'a' => $transaction['a'],
                    'r' => $transaction['r'],
                    'ty' => $transaction['ty'],
                    
                ];
            endforeach;

            $out[$k] = array_reverse($out[$k]);
        endforeach;
    return $out;
    }

    public function getPublicTransactionsV2($attr){ 
        foreach ($this->dashMarket($attr) as $v):
            $arr[$v] = 'https://'.$this->url_api2.'/rest/trading/transactions/'.$v.'?limit=15';
        endforeach;
        
        $results = $this->MyMultiCurl($arr);

        if($results):
            foreach ($results as $k => $value):
                $d = json_decode($value, true);
                $cw = explode('-', $k)[0];
                if($d['items']):
                    foreach ($d['items'] as $transaction):
                        $out[$cw][$k][] = [
                            'id' => $transaction['id'],

                            't' => $transaction['t'],
                            'a' => $transaction['a'],
                            'r' => $transaction['r'],
                            'ty' => $transaction['ty'],
                        ];
                    endforeach;

                    $out[$cw][$k] = array_reverse($out[$cw][$k]);
                endif;
            endforeach;
        endif;
    return $out;
    }

}//end class
?>