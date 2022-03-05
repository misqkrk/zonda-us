<?php
class PublicTransactions extends WebsocketV2{
    public function getTransactions($attr){
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

                preg_match('/transactions\/(.*)/', $json['topic'], $match1 );
                $pair =  strtoupper($match1[1]);
                $cw = explode('-', $pair)[0];
                //print_r($cw);
                if($json['message']['transactions']):
                    foreach ($json['message']['transactions'] as $k => $v):

                        $this->transactionsV2[$cw][$pair][] = [
                            'id' => $v['id'],
                            't' => $v['t'],
                            'a' => $v['a'],
                            'r' => $v['r'],
                            'ty' => $v['ty'],
                        ];

                    endforeach;  
                    $this->transactionsV2[$cw][$pair] = $this->sliceAndReverse($this->transactionsV2[$cw][$pair], $this->parm['limitWs']);
                    $this->redis->set('BB:WS:PUBLIC:TRANSACTIONS:'.$cw, igbinary_serialize($this->transactionsV2[$cw]), 62);


                    //$this->$transactions[$pair] = $this->sliceAndReverse($this->$transactions[$pair], $this->parm['limitWs']); //kosz
                    //$this->redis->set('BB:WS:PUBLIC:transactions', igbinary_serialize($this->$transactions), 62); //kosz


                    echo $pair.' ### '.PHP_EOL;
                    $this->status_PID(get_class($this),'last');

                elseif($json['action'] == 'pong'):
                    //$this->redis->set('BB:WS:PUBLIC:transactions', igbinary_serialize($this->$transactions), 62);

                    foreach ($attr as $cww => $v):
                        $this->redis->set('BB:WS:PUBLIC:TRANSACTIONS:'.$cww, igbinary_serialize($this->transactionsV2[$cww]), 62);
                    endforeach;

                    $this->status_PID(get_class($this),'keep');
                    echo 'PONG'.PHP_EOL;

                elseif($json['error']):
                    echo 'ERROR: '.$json['module'].'/'.$json['path'].' | '.$json['error'].PHP_EOL;
                    $conn->close();
                    $loop->stop();

                elseif($json['action']):
                    echo 'OK: '.$json['module'].'/'.$json['path'].' | '.$json['action'].PHP_EOL;

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
            //print_r($attr);
            $this->transactionsV2 = $this->getPublicTransactionsV2($attr);
            //$this->transactions = $this->getPublicTransactions($attr); //kosz

            foreach ($this->dashMarket($attr) as $pair):
                $tmp_attr = [
                    'action' => 'subscribe-public',
                    'module' => 'trading',
                    'path' => '/transactions/'.$pair,
                ];
                $cw = explode('-', $pair)[0];
                $this->redis->set('BB:WS:PUBLIC:TRANSACTIONS:'.$cw, igbinary_serialize($this->transactionsV2[$cw]), 62);

                
                $conn->send($this->subscribe($tmp_attr, true));
                //$this->subscribe($tmp_attr);
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