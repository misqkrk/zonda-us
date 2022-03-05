<?php
class PrivateTransactions extends WebsocketV2{

public function private_transactions($attr = null){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $connector($this->url_websocket())->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {
        


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG             
                $json = $this->replaceToJSON($msg);
                
                if($json['message']['offerId']):

                    $add_sql = [
                        'id' => $json['message']['id'],
                        'market' => $json['message']['market'],
                        'time' => $json['message']['time'],
                        'amount' => $json['message']['amount'],
                        'rate' => $json['message']['rate'],
                        'initializedBy' => $json['message']['initializedBy'],
                        'wasTaker' => $json['message']['wasTaker'],
                        'userAction' => $json['message']['userAction'],
                        'offerId' => $json['message']['offerId'],
                        'ws' => 1,
                    ];
                    $this->DB->insertOne('BB_transactions', $add_sql, 'INSERT IGNORE');

                    echo $log = $json['message']['market'].PHP_EOL;
                    $this->add_log($log);
                    $this->status_PID(get_class($this),'last');
                    $this->redis->set('B3:run', true, 300); // uruchomienie buy b3 tylko jak jakas transakcja


                elseif($json['action'] == 'pong'):
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
                'module' => 'trading',
                'path' => '/history/transactions',
                'hashSignature' => $sign,
                'publicKey' => $this->api_key,
                'requestTimestamp' => $time,
            ];
            $set_request = $this->subscribe($tmp_attr, true);
            $conn->send($set_request); 

            /////////////////////// START END

            $this->status_PID(get_class($this), null, $attr).PHP_EOL;

        }, function(\Exception $e) use ($loop) {
            echo $log = "Could not connect: {$e->getMessage()}\n";
            $this->add_log($log);
            $loop->stop();
        });

        $loop->run();
    }
}
?>