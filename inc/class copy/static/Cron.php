<?
class Cron{

	static function CMC_PERCENT_DIFFERENCE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    CMC_percentage_differences();
	}

	static function BB_UPDATE_BALANCE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    BALANCE::update_rest();
	}

	static function UPDATE_EXCHANGE_NBP($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    Exchange::UpdateCourseNBP();
	}

	static function CRYPTOWATCH($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    update_cryptowatchPrice();
	}

	static function UPDATE_COURSE_BTC_ETH($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    Exchange::UpdateCourseBTC();

	}

	static function CHANNEL_COURSE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    Exchange::SetWsCourse();
	}

	static function CHANNEL_PP($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    set_ws_bot_pp();
	}

	static function CHANNEL_BALANCE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    BALANCE::publish_ws();
	}

	static function CHANNEL_PLANS($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    FC::set_ws_choice_plan_orders();
	}

	static function UPDATE_CONFIG_SCALE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    update_config_scale();
	}

	static function BB_UPDATE_OPENORDERS($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    updateOpenOrders();
	}

	static function COINPAPRICA_REST($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    update_coinpaprika();
	}

	static function COINPAPRICA_VOLUMES($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    update_volumes_bb_coinpaprica();
	}

	static function BITMEX_LONG_SHORT($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    set_bitmex_short_long();
	}

	static function UPDATE_EXCHANGE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    $log = Exchange::update();
	    error_log(date("Y-m-d H:i:s").' || RETURTN currency_exchange: '.$log.' '.PHP_EOL,3, '/var/www/log/cron.log');

	}

	static function CHECK_BB_CW($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    websocket_check_v2();
	}

	static function CMC_REST($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    update_coinmarketcap_pro();
	}

	static function CLEAR_B2_B3($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    Clear::B2();
	    Clear::B3();
	}

	static function CLEAR_BALANCE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    Clear::balance();
	}


	static function TELEGRAM_DEL_MESSAGE($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    delete_telegram_message();
	}



	static function UPDATE_FC_COST($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    FC::update_cost();
	}

	static function CHECK_BB_RUN($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    redis_scan_check();
	}

	static function COINPAPRICA_TWITTER($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    coinpaprika_twitter();
	}

	static function BIN_LEADERBOARD($time = 0){
	    Cron::function_update_cron($time,__FUNCTION__);
	    BinanceLeaderboardCron();
	}

	static function PING($time = 0){
	   Cron::function_update_cron($time,__FUNCTION__);
	   ping(true);
	}

	static function GENERATE_LEADERBOARD_DAY($time = 0){
		//$date_plus_day = strtotime(date('Y-m-d'). '+ 1 day');
	   Cron::function_update_cron($time,__FUNCTION__);
	   leaderboard_generate_day();
	}

////////////////////
	static function function_update_cron($time = 1, $name){ //dodanie czasu do kolejnego wykonania crona
	    $DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

	    $next = strtotime('+'.($time * 60).' seconds',time());
	    //$name = str_replace('function_cron_', '', $name) ;
	    $DB->execute("UPDATE cron SET last = NOW(), next = '".$next."' WHERE name = '".$name."' ");
	    error_log(date("Y-m-d H:i:s").' || Function: '.$name.' '.PHP_EOL,3, '/var/www/log/cron.log');
	}

	static function run($type = 'MAIN'){ //wykonanie crona
	    $DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

	    $result = $DB->fetchAll("SELECT * FROM `cron` WHERE next < '".time()."' AND `on` = 1 AND `type` = '".$type."'  ORDER BY next LIMIT 2");
	    if($result):
	        foreach ($result as $key => $value):
	            $func = $value['name'];
	            if(method_exists('Cron', $func)) :
	                Cron::$func($value['min']);
	                echo $func.PHP_EOL;
	            endif;
	        endforeach;
	        
	        sleep(1);
	    else:
	        sleep(2);
	    endif;
	}


}