<?php
class CmcPrice extends WebsocketV2{

    public function Price($attr = []){
        $loop = React\EventLoop\Factory::create();
        $reactConnector = new React\Socket\Connector($loop);
        $connector = new Ratchet\Client\Connector($loop, $reactConnector);
        $url = 'wss://stream.coinmarketcap.com/price/latest';
        $connector($url)->then(function(Ratchet\Client\WebSocket $conn) use ($attr, $loop) {


            $conn->on('message', function($msg) use ($conn, $attr, $loop) {
            /////////////////////// MSG    
                $minute = 30000;

                $json = $this->replaceToJSON($msg);
                 if($json['d']):
                    $item = $json['d']['cr'];
                    $t = $json['d']['t'];

                    $this->cmc['items'][$item['id']]['rate'] = $item['p'];
                    $this->cmc['items'][$item['id']]['t'] = $t;
                    $this->cmc['items'][$item['id']]['time'] = $this->timeMs($t);
                    $this->cmc['items'][$item['id']]['changes']['1h'] = round($item['p1h'],4);
                    $this->cmc['items'][$item['id']]['changes']['24h'] = round($item['p24h'],4);
                    $this->cmc['items'][$item['id']]['changes']['7d'] = round($item['p7d'],4);

                    if(!$this->cmc_tmp['items'][$item['id']]['history_price']):
                        $this->cmc_tmp['items'][$item['id']]['history_price'][$t] = $item['p'];
                        $this->cmc_tmp['items'][$item['id']]['last_time'] = $t;

                    elseif($this->cmc_tmp['items'][$item['id']]['last_time'] + $minute < $t):
                        $this->cmc_tmp['items'][$item['id']]['history_price'][$t] = $item['p'];
                        $this->cmc_tmp['items'][$item['id']]['last_time'] = $t;
                        $this->cmc_tmp['items'][$item['id']]['history_price'] = array_slice($this->cmc_tmp['items'][$item['id']]['history_price'], -32, null, true);
                        $this->cmc_tmp['items'][$item['id']]['latest_price'] = $item['p'];

                        $history_price = $this->cmc_tmp['items'][$item['id']]['history_price'];
                        krsort($history_price);

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + $minute < $t ):
                              $this->cmc['items'][$item['id']]['changes']['30s'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + ($minute * 2) < $t ):
                              $this->cmc['items'][$item['id']]['changes']['1m'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + ($minute * 4) < $t ):
                              $this->cmc['items'][$item['id']]['changes']['2m'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + ($minute * 6) < $t ):
                              $this->cmc['items'][$item['id']]['changes']['3m'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + ($minute * 10) < $t ):
                              $this->cmc['items'][$item['id']]['changes']['5m'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + ($minute * 20) < $t ):
                              $this->cmc['items'][$item['id']]['changes']['10m'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                        foreach ($history_price as $f_time => $f_val):
                            if($f_time + ($minute * 30) < $t ):
                              $this->cmc['items'][$item['id']]['changes']['15m'] = roznica_procent($f_val,$item['p'],4);
                              break;  
                            endif;
                        endforeach;

                    else:


                    endif;
                    //print_r($this->cmc);
                    echo 'OK - '.$item['id'].PHP_EOL;


                    $this->redis->set('CMC:price:latest', igbinary_serialize($this->cmc) );
                    $this->status_PID(get_class($this),'last');

                 else:
                     print_r($json);
                     echo $log = 'BŁĄD NIEZNANY.'.PHP_EOL;
                     $this->add_log($log);
                 endif;
            });

            /////////////////////// MSG END

            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                echo $log = "CONNECTION CLOSED: {$code} - {$reason}".PHP_EOL;
                $loop->stop();
            });

            /////////////////////// START
            foreach (CW_ID_PAIR as $cw => $id):
                if($id['cmc']):
                    $ids[] = (int) $id['cmc'];
                endif;
            endforeach;

            $tmp_attr = [
                'method' => 'subscribe',
                'id' => 'price',
                'data' => [
                    'cryptoIds' => $ids,
                    'index' => null,
                ],
            ]; 

            $conn->send($this->subscribe($tmp_attr, true));
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