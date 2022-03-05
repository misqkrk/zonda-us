<?php
class BinancePublicKline extends WebsocketV2{

    protected function sliceKline($tmpArr, $limit = 120, $preserve_keys = true){
        if($tmpArr['items']):
            $tmpArr['items'] = array_reverse($tmpArr['items'], $preserve_keys);
            $tmpArr['items'] = array_slice($tmpArr['items'],0, $limit, $preserve_keys);
            //$out['items'] = array_reverse($out['items'], $preserve_keys);

        endif;
    return $tmpArr;
    }

    public function getKline($attr = []){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $url = 'wss://stream.binance.com:9443/stream?streams=btcusdt@kline_1m';
        $connector($url)->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG    

                $json = $this->replaceToJSON($msg);
//print_r($json);
                if($json['stream']) :
                    $ex = explode('@', $json['stream']);
                    $pair = strtoupper($ex[0]);
                    $mode = strtoupper($ex[1]);

                    $key = ($json['data']['k']['t']/1000);
                    if($json['data']['k']['x']):
                        $last_full_invertal = $key;
                    $this->kline[$pair]['last_full_key'] = $last_full_invertal; 
                    endif;
                    $this->kline[$pair]['timestamp'] = $json['data']['E']; 
                    $this->kline[$pair]['pair'] = $pair; 
                    $this->kline[$pair]['items'][$key] = $json['data']['k']; 
                    $this->kline[$pair]['items'][$key]['up'] = $up = ($json['data']['k']['c'] >= $json['data']['k']['o'] ? 'up' : 'down' ); 

                    $out = $this->sliceKline($this->kline[$pair]);
                    //print_r($out);
                    echo $pair.' ### O: '.my_number($json['data']['k']['o']).' | C: '.my_number($json['data']['k']['c']).' | '.$up.PHP_EOL;
                    $this->redis->set('BINANCE:WS:KLINE:'.$mode.':'.$pair, igbinary_serialize($out), 62);
                    $this->status_PID(get_class($this),'last');
                else:
                    print_r($json['e']);
                    echo $log = 'BŁĄD NIEZNANY.'.PHP_EOL;
                    //$this->add_log($log);

                endif;
            });

            /////////////////////// MSG END

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $loop->stop();
            });

            /////////////////////// START
            /*$this->$ticker = Curl::single('https://'.$this->url_api2.'/rest/trading/ticker',true);
            $tmp_attr = [
                'action' => 'subscribe-public',
                'module' => 'trading',
                'path' => '/ticker',
            ];
            $conn->send($this->subscribe($tmp_attr, true));
            /////////////////////// START END*/

            $this->status_PID(get_class($this)).PHP_EOL;

        }, function(\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }    
////
}
?>