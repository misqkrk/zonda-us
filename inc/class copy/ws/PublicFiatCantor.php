<?php
class PublicFiatCantor extends WebsocketV2{
   public function getFiatCantor($attr){
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

                if($json['message']):
                    preg_match('/fiat_cantor\/rate\/(.*)/', $json['topic'], $match1 );
                    $pair = str_replace('/', '-', strtoupper($match1[1]));
                    $this->$fiat_cantor[$pair] = $json['message'];

                    $this->redis->set('BB:WS:PUBLIC:fiat_cantor', igbinary_serialize($this->$fiat_cantor), 62);
                    $this->status_PID(get_class($this),'last');
                    echo $pair.' : ' .$json['message']['rate']. ''.PHP_EOL;

                elseif($json['action'] == 'pong'):
                    $this->redis->set('BB:WS:PUBLIC:fiat_cantor', igbinary_serialize($this->$fiat_cantor), 62);
                    $this->status_PID(get_class($this),'keep');
                    echo 'PONG'.PHP_EOL;

                elseif($json['error']):
                    echo 'ERROR: '.$json['module'].$json['path'].' | '.$json['error'].PHP_EOL;
                    $conn->close();
                    $loop->stop();

                elseif($json['action']):
                    echo 'OK: '.$json['module'].$json['path'].' | '.$json['action'].PHP_EOL;

                else:
                    print_r($json);
                endif;
            /////////////////////// MSG END
            });

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $loop->stop();
            });
            
            /////////////////////// START
            foreach ($attr as $market):
                $tmp_attr = [
                    'action' => 'subscribe-public',
                    'module' => 'fiat_cantor',
                    'path' => '/rate/'.$market,
                ];
                $conn->send($this->subscribe($tmp_attr, true));
                //print_r( $this->subscribe($tmp_attr, true)  );
// wss://api.bitbay.net/websocket/
// wss://router2.bbdevzone.com/websocket

// {"action":"subscribe-public","module":"fiat_cantor","path":"\/rate\/PLN\/EUR"}
// {"action":"subscribe-public","module":"fiat_cantor","path":"\/rate\/EUR\/PLN"}
            endforeach;
            /////////////////////// START END

            $this->status_PID(get_class($this)).PHP_EOL;

        }, function(\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }

}
?>