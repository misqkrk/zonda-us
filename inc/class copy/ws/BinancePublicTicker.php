<?php
class BinancePublicTicker extends WebsocketV2{



    public function getTicker($attr = []){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $url = 'wss://stream.binance.com:9443/stream?streams=!ticker@arr';
        $connector($url)->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG    
                $t1 = microtime(true);

                $json = $this->replaceToJSON($msg);
                if($json['stream']) :

                    foreach ($json['data'] as $v):
                        $this->binance_ticker['items'][$v['s']] = $v;
                    endforeach;

                    //$this->ticker['timestamp'] = $json['data']['E']; 
                    //$this->ticker['items'][$pair] = $json['data']; 

                    //print_r($out);
                    //echo $pair.' ### LAST: '.my_number($json['data']['c']).' SELL: '.my_number($json['data']['a']).' BUY: '.my_number($json['data']['b']).PHP_EOL;
                    $this->redis->set('BINANCE:WS:ticker', igbinary_serialize($this->binance_ticker), 62);
                    
                    $this->status_PID(get_class($this),'last');
                    
                    echo 'Time - '.round((microtime(true) - $t1),4).PHP_EOL;
                else:
                    //print_r($json['e']);
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