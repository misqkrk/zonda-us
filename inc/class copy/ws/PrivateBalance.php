<?php
class PrivateBalance extends WebsocketV2{
    public function balances($attr = null){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $connector($this->url_websocket())->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {
        


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG               
                $json = $this->replaceToJSON($msg);
                if($json['message']['id']):
                    $this->balances['balances'][$json['message']['id']] = $json['message'];
                    $this->balances['balances'][$json['message']['id']]['timestamp'] = $json['timestamp'];
                    $this->balances['status'] = 'Ok';
                    $this->balances['timestamp'] = time();

                    $this->redis->set('BB:WS:PRIVATE:balances', igbinary_serialize($this->balances), 62);

                    $this->status_PID(get_class($this),'last');

                    echo $log = 'SeqNo: '.$json['seqNo']. ' || '.$json['message']['currency'].' || Available: '.$json['message']['availableFunds'].' + Locked: '.$json['message']['lockedFunds'].PHP_EOL;
                    $this->add_log($log);
                    BALANCE::publish_ws(); // przerobienie i wyslanie message

                elseif($json['action'] == 'pong'):
                    $this->balances['timestamp'] = time();
                    $this->redis->set('BB:WS:PRIVATE:balances', igbinary_serialize($this->balances), 62);

                    $this->status_PID(get_class($this),'keep');
                    echo $log = 'PONG'.PHP_EOL;
                    $this->add_log($log);
                    BALANCE::publish_ws(); // przerobienie i wyslanie message


                elseif($json['error']):
                    echo $log = 'ERROR: '.$json['module'].$json['path'].' | '.$json['error'].PHP_EOL;
                    $this->add_log($log);
                    $conn->close();
                    $loop->stop();

                elseif($json['action']):
                    echo $log = 'OK: '.$json['module'].'/'.$json['path'].' | '.$json['action'].PHP_EOL;
                    $this->add_log($log);

                    //// PING - PONG START 
                    $loop->addPeriodicTimer($this->ping_invertal, function () use ($conn)  {
                        $conn->send($this->subscribe(['action' => 'ping']));  
                        echo  "SEND: ping".PHP_EOL;        
                    });
                    //// PING - PONG END

                else:
                    print_r($json);
                    echo $log = 'BŁĄD NIEZNANY.'.PHP_EOL;
                    $this->add_log($log);   

                endif;
            /////////////////////// MSG END
            });

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $this->add_log_CW($log);
                $loop->stop();
            });
            
            /////////////////////// START
            $time = time();
            $sign = hash_hmac("sha512", $this->api_key.$time, $this->api_secret);

            $tmp_attr = [
                'action' => 'subscribe-private',
                'module' => 'balances',
                'path' => '/balance/BITBAY/updateFunds',
                'hashSignature' => $sign,
                'publicKey' => $this->api_key,
                'requestTimestamp' => $time,
            ];
            
            $API = new API(BB_API);
            $this->balances = $API->wallets();
            //print_r($this->balances);
            if($this->balances['status'] == 'Ok' ):
                $set_request = $this->subscribe($tmp_attr, true);
                $conn->send($set_request);
            else:
                $loop->stop();
            endif;
            /////////////////////// START END

            $this->status_PID(get_class($this)).PHP_EOL;

        }, function(\Exception $e) use ($loop) {
            echo $log = "Could not connect: {$e->getMessage()}\n";
            $this->add_log($log);
            $loop->stop();
        });

        $loop->run();
    }
}
?>