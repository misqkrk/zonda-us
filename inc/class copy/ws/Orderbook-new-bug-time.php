<?php
class Orderbook extends WebsocketV2{
    protected function orderbook_info($json = null, $cw = null, $is_start = null){
        $this->info['cw'] = $cw;
        $this->info['timestamp'] = $json['timestamp'];
        $this->info['difference_ms'] = (int) (microtime(true) * 1000) - $json['timestamp'];
        
        if($this->ws_count > 1000): // reset
            $this->ws_count = 0 ;
            $this->ws_count_sum = 0;
        endif;

        if($this->info['difference_ms']): //jezeli mniej jak 150ms
            $this->ws_count++;
            $this->ws_count_sum = $this->ws_count_sum + $this->info['difference_ms'] ;
            $this->info['avg_count'] = $this->ws_count;                            
            $this->info['avg_time'] = (int) ($this->ws_count_sum / $this->ws_count);
        endif;       

        if($is_start): // wbicie na start
            $this->info['difference_ms'] = 50;
            $this->info['avg_count'] = 1;                            
            $this->info['avg_time'] = 50;
            $this->info['timestamp'] = microtime(true);
        endif;

    }

    protected function orderbook_rest_api($attr){
        if(!isset($this->ws_i)) $this->ws_i = 0;
        if(!isset($this->ws_t)) $this->ws_t = 0;
        $this->ws_i++;
        
        if($this->ws_t + $this->new_rest_orderbook_time < time()):
            echo 'GET REST ORDERBOOK !'.PHP_EOL;
            sleep(2);
            $this->$orderbook = $this->getPublicOrderbook($attr); 
            $this->ws_t = time();
            $this->ws_i = 0;
        endif;       
    }

    protected function przeciwstawne_oferty($v, $pair){
        $tmp_f = null;
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
            endswitch;
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

            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG 
      
                $t1 = microtime(true);
                $cw =  key($attr);
                $json = $this->replaceToJSON($msg);
                
                /////////////////////// REST API co X czasu 
                $this->orderbook_rest_api($attr);
                /////////////////////// END REST API co X czasu
 
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
                            
                                 // USUNIECIE PRZECIWSTAWNYCH OFERT
                                $this->przeciwstawne_oferty($v, $pair); // new test
                                // END

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


                    /// TMP
/*                    
                    $tmp_out .= '===== SELL'.PHP_EOL;

                    $ss = $this->orderbook_memcache[$cw.'-BTC']['Sell'];
                    krsort($ss);
                    foreach ($ss as $tk => $tv) {
                        $tmp_out .= $tv['ra'].' || '.$tv['ca'].PHP_EOL;
                    }
                    $tmp_out .= '===== BUY'.PHP_EOL;
                    $bb = $this->orderbook_memcache[$cw.'-BTC']['Buy'];
                    krsort($bb);
                    foreach ($bb as $tk => $tv) {
                        $tmp_out .= $tv['ra'].' || '.$tv['ca'].PHP_EOL;
                    }
                    $tmp_out .= '===== END'.PHP_EOL;
                    echo $tmp_out;*/
                    
                    /// END TMP




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

                    

                    $this->redis->publish('channel-'.$cw, igbinary_serialize($this->orderbook_memcache_3) ); 
                    
                    $this->redis->set('BB:WS:PUBLIC:ORDERBOOK:'.$cw,igbinary_serialize($this->orderbook_memcache),62);
                    $this->redis->set('INFO:WS:ORDERBOOK:'.$this->info['channel'],igbinary_serialize($this->info),62); 

                    //echo 'KEY: '.$tmp_uniq_key.' | SERV TIME: '.$this->timeMs().PHP_EOL;

                    //$this->test_sum += round((microtime(true) - $t1),4);
                    //$this->test_i++;
                    //echo 'SPEED ['.$this->test_i.']: '.round($this->test_sum / $this->test_i,4).PHP_EOL;

                    echo '####################################### '.$pair.' ### Time - '.round((microtime(true) - $t1),4).' | Difference: '.$this->info['difference_ms'].'ms.'.PHP_EOL;

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
            $this->$orderbook = $this->getPublicOrderbook($attr); 
            /////////////////////// START END


            /////////////////////// INFO
            $this->orderbook_info(null, $cw, true);
            /////////////////////// INFO END

            $limit_ws_items = ($cw === 'BTC' ? 7 : 5);

            foreach ($this->$orderbook as $tmpPair => $tmpArr):
                $this->orderbook_memcache[$tmpPair] = $this->sortAndSlice($tmpArr, $this->parm['limitWs'] , false);
                $tmpOrderbook = null; //??
                $tmpOrderbook = $this->sortAndSlice($tmpArr, $this->parm['limitWs'], false); //??
                $this->orderbook_memcache[$tmpPair] = $tmpOrderbook; //??
                $this->orderbook_memcache_3[$tmpPair] = $this->slice($tmpOrderbook,$limit_ws_items,false);
            endforeach;

            $this->orderbook_memcache['info']['key'] = $this->GetUUID(random_bytes(16));
            $this->orderbook_memcache_3['info']['key'] = $this->GetUUID(random_bytes(16));
            $this->orderbook_memcache_3['info']['ws_send_time'] = $this->timeMs();

            $this->redis->publish('channel-'.$cw, igbinary_serialize($this->orderbook_memcache) );
            $this->redis->set('BB:WS:PUBLIC:ORDERBOOK:'.$cw,igbinary_serialize($this->orderbook_memcache),62);
            $this->redis->set('INFO:WS:ORDERBOOK:'.$this->info['channel'],igbinary_serialize($this->info),62); 

            // END WBICIE NA START
            

        }, function(\Exception $e) use ($loop) {
            echo "Could not connect: {$e->getMessage()}\n";
            $loop->stop();
        });

        $loop->run();
    }
}