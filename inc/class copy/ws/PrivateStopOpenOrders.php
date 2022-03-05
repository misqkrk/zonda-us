<?php
class PrivateStopOpenOrders extends WebsocketV2{
 public function offers($attr = null){
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
                //print_r($json);
                if($json['message']['action']):
                    if($json['message']['action'] == 'active'):

                    elseif($json['message']['action'] == 'cancelled'):

                    elseif($json['message']['action'] == 'triggered'):

                    elseif($json['message']['action'] == 'accepted'):

                    elseif($json['message']['action'] == 'rejected'):
                    
                    endif;
                    $this->offers['items'][$json['message']['state']['offerId']] = $json['message']['state'];
                    $this->offers['items'][$json['message']['state']['offerId']]['additional'] = $this->open_orders_stop_additional($json['message']['state']);


                    $this->offers['serverTime'] = $this->timeMs();
                    $this->offers['status'] = 'Ok';


                    $this->redis->set('BB:WS:PRIVATE:stop_open_orders', igbinary_serialize($this->offers), 62);

                    $this->status_PID(get_class($this),'last');

                elseif($json['action'] == 'pong'):

                    $this->redis->set('BB:WS:PRIVATE:stop_open_orders', igbinary_serialize($this->offers), 62);

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
                print_r($this->offers);
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
                'path' => '/stop/offers',
                'hashSignature' => $sign,
                'publicKey' => $this->api_key,
                'requestTimestamp' => $time,
            ];
            $set_request = $this->subscribe($tmp_attr, true);
            $conn->send($set_request);
            sleep(1);

            $API = new API($this->api_key,$this->api_secret);

            foreach ($API->openOrdersStop()['items'] as $k => $v):
                $this->offers['items'][$v['id']] = $v;
            endforeach;
//print_r($this->offers);
            $this->offers['status'] = 'Ok';
            $this->redis->set('BB:WS:PRIVATE:stop_open_orders', igbinary_serialize($this->offers), 62);

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