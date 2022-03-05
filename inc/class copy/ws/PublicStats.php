<?php
class PublicStats extends WebsocketV2{
    public function get_stats($attr = []){
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
                if($json['message']) :
                    foreach ($json['message'] as $v):
                        $this->$stats['items'][$v['m']] = $v;
                    endforeach;
                    $this->$stats['timestamp']= $json['timestamp'];

                    $this->redis->set('BB:WS:PUBLIC:stats', igbinary_serialize($this->$stats), 62);
                    $this->status_PID(get_class($this),'last');
                    echo 'UPDATE'.PHP_EOL;
                
                elseif($json['action'] == 'pong'):
                    $this->redis->set('BB:WS:PUBLIC:stats', igbinary_serialize($this->$stats), 62);
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

            });
            /////////////////////// MSG END


            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $loop->stop();
            });


            /////////////////////// START
            $tmp_attr = [
                'action' => 'subscribe-public',
                'module' => 'trading',
                'path' => 'stats',
            ];
            $this->$stats = Curl::single('https://'.$this->url_api2.'/rest/trading/stats',true);

            $conn->send($this->subscribe($tmp_attr, true));
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