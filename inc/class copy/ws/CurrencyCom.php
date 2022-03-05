<?php
class CurrencyCom extends WebsocketV2{


    public function get_currency($attr = []){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $url = 'wss://prod-pusher.backend-capital.com/app/MvtsstCbm?protocol=7&client=js&version=4.2.2&flash=false';
        $connector($url)->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG    
                $json = $this->replaceToJSON($msg);
                if($json['event'] == 'bbo'):
                    $data = $this->replaceToJSON($json['data']);
                    $pair = $attr[$json['channel']];

                    $item = [
                        'channel' => $json['channel'],
                        'pair' => $pair,
                        'bid' => $data['bid'],
                        'ask' => $data['ask'],
                        'avg' =>  round( ($data['bid'] + $data['ask'])/2 ,4),
                        't' => $data['ts'],
                        'th' => $this->timeMs($data['ts']),
                        'ths' => $this->timeMs(),

                    ];
                    $this->$offers['items'][$pair] = $item;
                    $this->$offers['time'] = $this->timeMs();
                    $this->redis->set('CURRENCYCOM:WS:currency', igbinary_serialize($this->$offers), 62);

                    $this->status_PID(get_class($this),'last');

                    echo $pair.' ### AVG: '.$item['avg'].' | BID: '.$item['bid'].' | ASK: '.$item['ask'].PHP_EOL;

            
                elseif($json['event'] == 'pusher_internal:subscription_succeeded'):
                    echo $log = 'Subscription Succeeded: '.$attr[$json['channel']].PHP_EOL;
                    $this->add_log($log);


                elseif($json['event'] == 'pusher:connection_established'):
                    echo $log = 'Connection Established: '.$json['data'].PHP_EOL;
                    $this->add_log($log);


                else:
                    $log = 'ELSE: '.print_r($json, true).PHP_EOL;
                    $this->add_log($log);
                    print_r($json);
                endif;

            });

            /////////////////////// MSG END

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $this->add_log($log);

                $loop->stop();
            });

            /////////////////////// START

            foreach ($attr as $key => $value):
                $arr_start = [
                    'event' => 'pusher:subscribe',
                    'data' => [
                        'channel' => (string) $key,
                    ],
                ];
                echo $this->subscribe($arr_start, true).PHP_EOL;
                $conn->send($this->subscribe($arr_start, true));
            endforeach;

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