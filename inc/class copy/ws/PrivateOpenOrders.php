<?php
class PrivateOpenOrders extends WebsocketV2{
 public function offers($attr = null){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $connector($this->url_websocket())->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {
        
            // $this->timer = $loop->addTimer(60, function () use ($conn)  { //musi byc na start
            //     echo 'Minął czas braku odpowiedzi - STOP'.PHP_EOL;
            // });

            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG               
                $json = $this->replaceToJSON($msg);

                // $loop->cancelTimer($this->timer); // anulowanie zabicia z powodu braku odpowiedzi
                // $this->timer = $loop->addTimer(60, function () use ($conn, $loop)  {
                //     echo 'Minął czas braku odpowiedzi - Restart'.PHP_EOL;
                //     $loop->stop();
                // });    

                //print_r($json);
                if($json['message']['action']):
                    if($json['message']['action'] == 'update'):
                        $this->offers['items'][$json['message']['offerId']] = $json['message']['state'];
                        $this->offers['items'][$json['message']['offerId']]['additional'] = $this->open_orders_additional($json['message']['state']);
                        $tmp_add = [
                            'market' => $json['message']['state']['market'],
                            'type' => $json['message']['state']['offerType'],
                            'rate' => $json['message']['state']['rate'],
                            'realization' => $this->offers['items'][$json['message']['offerId']]['additional']['realizationPercent'],
                        ];
                        echo $log = 'Update: '.$json['message']['state']['market'].' || ID: '.$json['message']['offerId'].PHP_EOL;
                        $this->add_log($log);

                    else:
                        unset($this->offers['items'][$json['message']['offerId']]);
                        $tmp_add = [
                            'market' => $json['message']['market'],
                            'rate' => $json['message']['rate'],
                            'type' => 'Remove',
                        ];
                        echo $log = 'Remove: '.$json['message']['market'].' || ID: '.$json['message']['offerId'].PHP_EOL;
                        $this->add_log($log);
                        
                    endif;
                    $this->offers['serverTime'] = $this->timeMs();
                    $this->offers['status'] = 'Ok';

                    if($this->offers['items']):
                        foreach ($this->offers['items'] as $v): // dane do bota
                            $offers_bot[$v['market'].'-'.strtoupper($v['offerType'])][] = $v['rate'];
                        endforeach;
                        $this->redis->set('BB:WS:PRIVATE:open_orders_bot', igbinary_serialize($offers_bot), 62);
                    endif;
                    $this->redis->set('BB:WS:PRIVATE:open_orders', igbinary_serialize($this->offers), 62);
                    $this->redis->publish('channel-open_orders', igbinary_serialize( $offers_bot ) ); 

                    $this->status_PID(get_class($this),'last');

                elseif($json['action'] == 'pong'):

                    if($this->offers['items']):
                        foreach ($this->offers['items'] as $v): // dane do bota
                            $offers_bot[$v['market'].'-'.strtoupper($v['offerType'])][] = $v['rate'];
                        endforeach;
                        $this->redis->set('BB:WS:PRIVATE:open_orders_bot', igbinary_serialize($offers_bot), 62);
                    endif;
                    $this->redis->set('BB:WS:PRIVATE:open_orders', igbinary_serialize($this->offers), 62);

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
                'path' => '/offers',
                'hashSignature' => $sign,
                'publicKey' => $this->api_key,
                'requestTimestamp' => $time,
            ];


            $API = new API(BB_API);
            $oo = $API->openOrders();
            if($oo['status'] == 'Ok' ):

                $set_request = $this->subscribe($tmp_attr, true);
                $conn->send($set_request);
                sleep(1);

                foreach ($oo['items'] as $k => $v):
                    $this->offers['items'][$v['id']] = $v;
                endforeach;

            else:
                $loop->stop();
            endif;



            $this->offers['status'] = 'Ok';
            //$this->memcache->set('BB:WS:PRIVATE:open_orders', $this->offers, 62); 
            $this->redis->set('BB:WS:PRIVATE:open_orders', igbinary_serialize($this->offers), 62);

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