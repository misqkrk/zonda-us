<?php
class OrderbookV2 extends WebsocketV2{
    protected function orderbook_info($json, $cw = null){
        $this->info['cw'] = $cw;
        $this->info['timestamp'] = $json['timestamp'];
        $this->info['difference_ms'] = (int) (microtime(true) * 1000) - $json['timestamp'];
        if($this->ws_count > 1000):
            $this->ws_count = 0 ;
            $this->ws_count_sum = 0;
        endif;

        //if($this->info['difference_ms'] < 150): //jezeli mniej jak 150ms
        if($this->info['difference_ms']): //jezeli mniej jak 150ms

            $this->ws_count++;
            $this->ws_count_sum = $this->ws_count_sum + $this->info['difference_ms'] ;
            $this->info['avg_count'] = $this->ws_count;                            
            $this->info['avg_time'] = (int) ($this->ws_count_sum / $this->ws_count);
        endif;        
    }



    protected function orderbook_rest_api($attr){
        if(!isset($this->ws_i)) $this->ws_i = 0;
        if(!isset($this->ws_t)) $this->ws_t = 0;
        $this->ws_i++;
        
        if($this->ws_t + $this->new_rest_orderbook_time < time()):
            echo 'GET REST ORDERBOOK !'.PHP_EOL;
            //sleep(2);
            $this->$orderbook = $this->getPublicOrderbook($attr); 
            $this->ws_t = time();
            $this->ws_i = 0;
        endif;       
    }

    public function getOrderbook($attr){
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

            $loop->addPeriodicTimer(11, function () use ($conn, $api_key, $api_secret, $attr)  {
                
                foreach ($this->dashMarket($attr) as $market):
                    $this->tmp_cw[$market] = $tmp_guid = $this->GetUUID(random_bytes(16));
                    $time = time();
                    $sign = hash_hmac("sha512", $this->api_key.$time, $this->api_secret);
                    $tmp_attr = [
                        'requestId' => $tmp_guid,
                        'action' => 'proxy',
                        'module' => 'trading',
                        'path' => 'orderbook/'.$market.'',
                        'hashSignature' => $sign,
                        'publicKey' => $this->api_key,
                        'requestTimestamp' => $time,
                    ];
                    $set_request = $this->subscribe($tmp_attr, true);
                    $conn->send($set_request);

                endforeach;

                echo  "SEND: proxy".PHP_EOL; 

            });


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG 
      
                $t1 = microtime(true);
                $cw =  key($attr);
                $json = $this->replaceToJSON($msg);
                
                /////////////////////// REST API co X czasu 
                //$this->orderbook_rest_api($attr);
                /////////////////////// END REST API co X czasu
 //print_r($this->$orderbook);
                if($json['message']['changes']):
                    foreach ($json['message']['changes'] as $k => $v):
                        $pair = $v['marketCode'];
                        switch ($v['action']):
                            case 'remove':                  
                                unset($this->$orderbook[$pair][$v['entryType']][$this->CourseToKey($v['rate'])]);
                                $tmp_uniq_key .= '|R:'.$v['marketCode'].':'.$v['rate'].'|';
                                echo 'REMOVE: '.$v['marketCode'].' - '.$v['entryType'].' - '.$v['rate']. PHP_EOL; //.$this->timeMs($json['timestamp'])
                                break;

                            default:
                            
                                //usunięcie przeciwstawnych ofert                  
/*                                $tmp_f = null;
                                $tmp_remove_key = null;

                                switch ($v['entryType']):
                                    case 'Buy':
                                        $tmp_remove_key = 'Sell';
                                        
                                        if($this->$orderbook[$pair][$tmp_remove_key]):
                                            $tmp_f = array_filter(array_keys($this->$orderbook[$pair][$tmp_remove_key]), function($n) use ($v){
                                              return $n <= $this->CourseToKey($v['state']['ra']);
                                            });
                                            if($tmp_f):
                                                foreach ($tmp_f as $fv):
                                                    unset($this->$orderbook[$pair][$tmp_remove_key][$fv]);
                                                    error_log($this->timeMs()." | ".$pair." ||".PHP_EOL, 3, DIR_LOG."cw_error_difference-".date("d-m-Y").".log");

                                                    echo '!!!!!!!!!!!!!!!!!!!!!!! KASUJEMY - SELL - FOREACH: '.$fv.PHP_EOL;
                                                endforeach;
                                            else:
                                                unset($this->$orderbook[$pair][$tmp_remove_key][$this->CourseToKey($v['state']['ra'])]);

                                                //echo 'KASUJEMY - SELL - ELSE: '.$this->CourseToKey($v['state']['ra']).PHP_EOL;
                                            endif;
                                        endif;
                                        break;

                                    case 'Sell':
                                        $tmp_remove_key = 'Buy';

                                        if($this->$orderbook[$pair][$tmp_remove_key]):
                                            $tmp_f = array_filter(array_keys($this->$orderbook[$pair][$tmp_remove_key]), function($n) use ($v){
                                              return $n >= $this->CourseToKey($v['state']['ra']);
                                            });
                                            if($tmp_f):
                                                foreach ($tmp_f as $fv):
                                                    unset($this->$orderbook[$pair][$tmp_remove_key][$fv]);
                                                    error_log($this->timeMs()." | ".$pair." ||".PHP_EOL, 3, DIR_LOG."cw_error_difference-".date("d-m-Y").".log");

                                                    echo '!!!!!!!!!!!!!!!!!!!!!!! - BUY - FOREACH:  '.$fv.PHP_EOL;
                                                endforeach;
                                            else:
                                                unset($this->$orderbook[$pair][$tmp_remove_key][$this->CourseToKey($v['state']['ra'])]);
                                                //echo 'KASUJEMY - BUY - ELSE: '.$this->CourseToKey($v['state']['ra']).PHP_EOL;
                                            endif;
                                        endif;
                                        break;
                                endswitch;*/
                                // END usunięcie przeciwstawnych ofert

                                //$this->$orderbook[$pair][$v['entryType']][$this->CourseToKey($v['rate'])] = $v['state'];
                                //$this->$orderbook[$pair][$v['entryType']][$this->CourseToKey($v['rate'])]['timestamp'] = $json['timestamp'];

                               $this->$orderbook[$pair][$v['entryType']][$this->CourseToKey($v['rate'])] = [
                                    'ra' => $v['state']['ra'],
                                    'ca' => $v['state']['ca'],
                                    'co' => $v['state']['co'],
                                    't' => $json['timestamp'],
                                ];

                                /// MONIT GRUBASÓW DLA BTC:
                                if( ($cw == 'BTC') && ($v['state']['ca'] > 5.99)  ):
                                    error_log($this->timeMs()." | ".$pair." || A: ".$v['state']['ca'].' | R: '.$v['state']['ra'].' | T: '.$v['entryType'].PHP_EOL, 3, DIR_LOG."BTC-big-offers-".date("d-m-Y").".log");
                                endif;
                                ///

                                $tmp_uniq_key .= '|U:'.$v['marketCode'].':'.$v['state']['ra'].':'.$v['state']['ca'].'|';
                                echo 'UPDATE: '.$v['marketCode'].' - '.$v['entryType'].' - '.$v['state']['ra']. PHP_EOL; //.$this->timeMs($json['timestamp'])
                                break;

                        endswitch;
                    endforeach;
                    /////////////////////// INFO
                    $this->orderbook_info($json, $cw);
                    /////////////////////// INFO END






                    $tmpOrderbook = null;
                    $tmpOrderbook = $this->sortAndSlice($this->$orderbook[$pair], $this->parm['limitWs'], false);
                    
                    if($tmpOrderbook['Sell'] && $tmpOrderbook['Buy']):                                
                        if( (float) $tmpOrderbook['Sell'][0]['ra'] > (float) $tmpOrderbook['Buy'][0]['ra'] ):
                            $this->orderbook_memcache[$pair] = $tmpOrderbook;
                            $this->orderbook_memcache_3['items'][$pair] = $this->slice($tmpOrderbook,$this->parm['limitWsRedis'],false);
                        else:
                            echo $log = 'NOTICE: kursy się rozjechały !'.PHP_EOL;
                            $this->add_log_CW($log);
                            //print_r($json);
                        endif;
                    else:
                        $this->orderbook_memcache[$pair] = $tmpOrderbook;
                        $this->orderbook_memcache_3['items'][$pair] = $this->slice($tmpOrderbook,$this->parm['limitWsRedis'],false);
                    endif;

                    ////
                    $tmp_uniq_key = crc32($tmp_uniq_key);
                    $market = explode('-', $pair)[1]; 

                    $tmp_mem_items = $this->orderbook_memcache_3['items'][$pair];
                    $key_redis = crc32(
                    $tmp_mem_items['Buy'][0]['ra'].$tmp_mem_items['Buy'][1]['ra'].$tmp_mem_items['Buy'][2]['ra'].$tmp_mem_items['Buy'][3]['ra'].$tmp_mem_items['Buy'][4]['ra'].
                    $tmp_mem_items['Buy'][0]['ca'].$tmp_mem_items['Buy'][1]['ca'].$tmp_mem_items['Buy'][2]['ca'].
                    $tmp_mem_items['Sell'][0]['ra'].$tmp_mem_items['Sell'][1]['ra'].$tmp_mem_items['Sell'][2]['ra'].$tmp_mem_items['Sell'][3]['ra'].$tmp_mem_items['Sell'][4]['ra'].
                    $tmp_mem_items['Sell'][0]['ca'].$tmp_mem_items['Sell'][1]['ca'].$tmp_mem_items['Sell'][2]['ca'].
                    date('i')
                    );

                    $this->orderbook_memcache['info'] = [
                        'key' => $tmp_uniq_key,
                        'ws_send_time' => $this->timeMs(),
                        'pair' => $pair,
                        'cw' => $cw,
                        'market' => $market,
                    ];

                    $this->orderbook_memcache_3['info'] = [
                        'key' => $tmp_uniq_key,
                        'key_redis' => $key_redis,

                        'ws_send_time' => $this->timeMs(),
                        'pair' => $pair,
                        'cw' => $cw,
                        'market' => $market,
                    ];

                    

                    $this->redis->publish('test-channel-'.$cw, igbinary_serialize($this->orderbook_memcache_3) ); 
                    
                    $this->redis->set('test-BB:WS:PUBLIC:ORDERBOOK:'.$cw,igbinary_serialize($this->orderbook_memcache),62);
                    $this->redis->set('test-INFO:WS:ORDERBOOK:'.$this->info['channel'],igbinary_serialize($this->info),62); 

                    

                    echo '####################################### '.$pair.' ### Time - '.round((microtime(true) - $t1),4).' | Difference: '.$this->info['difference_ms'].'ms. | seqNo: '.$json['seqNo'].PHP_EOL;


                elseif($json['action'] == 'proxy-response'):

                    $tmp_market  = array_search($json['requestId'], $this->tmp_cw);
                    echo 'OK: proxy-response | '.$tmp_market.' | seqNo: '.$json['body']['seqNo'].PHP_EOL;

                    if($json['body']):

                        //print_r($json['body']);
                        foreach ($json['body']['buy'] as $bid):
                            $this->$orderbook[$tmp_market]['Buy'][$this->CourseToKey($bid['ra'])] = [
                                'ra' => $bid['ra'],
                                'ca' => $bid['ca'],
                                'co' => $bid['co'],
                                't' => $json['body']['timestamp'],
                            ];
                        endforeach;

                        foreach ($json['body']['sell'] as $ask):
                            if( (float) $ask['ra'] < 1000000000):
                                $this->$orderbook[$tmp_market]['Sell'][$this->CourseToKey($ask['ra'])] = [
                                'ra' => $ask['ra'],
                                'ca' => $ask['ca'],
                                'co' => $ask['co'],
                                't' => $json['body']['timestamp'],
                            ];
                            endif;
                        endforeach;
                        $this->$orderbook[$tmp_market]['seqNo'] = $json['body']['seqNo'];
                        $this->$orderbook[$tmp_market]['timestamp'] = $json['body']['timestamp'];


                    endif;

                elseif($json['action'] == 'pong'):
                    $this->redis->set('BB:WS:PUBLIC:ORDERBOOK:'.$cw,igbinary_serialize($this->orderbook_memcache),62);
                    $this->redis->set('INFO:WS:ORDERBOOK:'.$this->info['channel'],igbinary_serialize($this->info),62); 

                    $this->redis->publish('channel-'.$cw, igbinary_serialize($this->orderbook_memcache_3) ); //?? pozniej wywalic
                    echo 'PONG'.PHP_EOL;

                elseif($json['error']):
                    echo $log = 'ERROR: '.$json['module'].'/'.$json['path'].' | '.$json['error'].PHP_EOL;
                    $this->add_log_CW($log);
                    $conn->close();
                    $loop->stop();

                elseif($json['action']):
                    echo $log = 'OK: '.$json['module'].'/'.$json['path'].' | '.$json['action'].PHP_EOL;
                    $this->add_log_CW($log);

                else:
                    print_r($json);
                    echo $log = 'ERROR (ELSE): '.$json['module'].'/'.$json['path'].' | '.$json['action'].PHP_EOL;
                    $this->add_log_CW($log);

                endif;

            });

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $this->add_log_CW($log);
                $loop->stop();
            });

            /////////////////////// START
            $cw =  explode('-', key($attr))[0]; // do przebudowania na wiecej mozliwosci par
           
            foreach ($this->dashMarket($attr) as $market):
                $tmp_attr = [
                    'action' => 'subscribe-public',
                    'module' => 'trading',
                    'path' => '/orderbook/'.$market,
                ];
                $conn->send($this->subscribe($tmp_attr, true));
            endforeach;

            foreach ($this->dashMarket($attr) as $market):
                $tmp_attr = [
                    'action' => 'subscribe-public',
                    'module' => 'trading',
                    'path' => '/orderbook/'.$market,
                ];
                $conn->send($this->subscribe($tmp_attr, true));
            endforeach;

            //$this->$orderbook = $this->getPublicOrderbook($attr); 
            /////////////////////// START END

            //WBICIE NA START
            $this->info['cw'] = $cw;
            $this->info['difference_ms'] = 20;
            $this->info['avg_count'] = 1;                            
            $this->info['avg_time'] = 20;
            $this->info['timestamp'] = microtime(true);




////



            foreach ($this->dashMarket($attr) as $market):
                $this->tmp_cw[$market] = $tmp_guid = $this->GetUUID(random_bytes(16));
                $time = time();
                $sign = hash_hmac("sha512", $this->api_key.$time, $this->api_secret);
                $tmp_attr = [
                    'requestId' => $tmp_guid,
                    'action' => 'proxy',
                    'module' => 'trading',
                    'path' => 'orderbook/'.$market.'',
                    'hashSignature' => $sign,
                    'publicKey' => $this->api_key,
                    'requestTimestamp' => $time,
                ];
                $set_request = $this->subscribe($tmp_attr, true);
                $conn->send($set_request);

            endforeach;

////



            // foreach ($this->$orderbook as $tmpPair => $tmpArr):
            //     $this->orderbook_memcache[$tmpPair] = $this->sortAndSlice($tmpArr, $this->parm['limitWs'] , false);
            //     $tmpOrderbook = null; //??
            //     $tmpOrderbook = $this->sortAndSlice($tmpArr, $this->parm['limitWs'], false); //??
            //     $this->orderbook_memcache[$tmpPair] = $tmpOrderbook; //??
            //     $this->orderbook_memcache_3[$tmpPair] = $this->slice($tmpOrderbook,$this->parm['limitWsRedis'],false);
            // endforeach;

            // $this->orderbook_memcache['info']['key'] = $this->GetUUID(random_bytes(16));
            // $this->orderbook_memcache_3['info']['key'] = $this->GetUUID(random_bytes(16));
            // $this->orderbook_memcache_3['info']['ws_send_time'] = $this->timeMs();


            //$this->redis->set('BB:WS:PUBLIC:ORDERBOOK:'.$cw,igbinary_serialize($this->orderbook_memcache),62);
            //$this->redis->set('INFO:WS:ORDERBOOK:'.$this->info['channel'],igbinary_serialize($this->info),62); 

            //$this->redis->publish('channel-'.$cw, igbinary_serialize($this->orderbook_memcache) );
            // END WBICIE NA START
            

        }, function(\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }
}