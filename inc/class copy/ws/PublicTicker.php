<?php
class PublicTicker extends WebsocketV2{
    public function getTicker($attr = []){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $connector($this->url_websocket())->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {
        
            //// PING - PONG START
            $loop->addPeriodicTimer($this->ping_invertal, function () use ($conn)  {
                $conn->send($this->subscribe(['action' => 'ping']));  
                echo  "SEND: ping".PHP_EOL;        
            });
            //// PING - PONG END
            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG    

                $json = $this->replaceToJSON($msg);
                
                if($json['topic'] == 'trading/ticker') :
                    $this->$ticker['time_websocket'] = $this->timeMs();
                    $this->$ticker['items'][$json['message']['market']['code']] = $json['message'];

                    $this->redis->set('BB:WS:PUBLIC:ticker', igbinary_serialize($this->$ticker), 62);

                    $this->status_PID(get_class($this),'last');
                    echo $log = $json['message']['market']['code'].' || BID: '.$json['message']['highestBid'].' || ASK: '.$json['message']['lowestAsk']. ' || SPREAD: '.roznica_procent($json['message']['highestBid'], $json['message']['lowestAsk']).' %'.PHP_EOL;

                    $this->add_log($log);

                elseif($json['action'] == 'pong'):
                    $this->redis->set('BB:WS:PUBLIC:ticker', igbinary_serialize($this->$ticker), 62);
                    $this->status_PID(get_class($this),'keep');
                    echo $log = 'PONG'.PHP_EOL;
                    $this->add_log($log);
                    
                elseif($json['error']):
                    echo $log = 'ERROR: '.$json['module'].'/'.$json['path'].' | '.$json['error'].PHP_EOL;
                    $this->add_log($log);
                    $conn->close();
                    $loop->stop();

                elseif($json['action']):
                    echo $log = 'OK: '.$json['module'].'/'.$json['path'].' | '.$json['action'].PHP_EOL;
                    $this->add_log($log);

                else:
                    print_r($json);
                    echo $log = 'BŁĄD NIEZNANY.'.PHP_EOL;
                    $this->add_log($log);

                endif;
            });

            /////////////////////// MSG END

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $loop->stop();
            });

            /////////////////////// START
            $this->$ticker = Curl::single('https://'.$this->url_api2.'/rest/trading/ticker',true);
            $tmp_attr = [
                'action' => 'subscribe-public',
                'module' => 'trading',
                'path' => '/ticker',
            ];
            $conn->send($this->subscribe($tmp_attr, true));
            /////////////////////// START END

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