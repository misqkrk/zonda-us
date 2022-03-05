<?

class T {
	static function send($arr = []){
		/*
		$arr = [
			'bot' => '', // string - bot
			'k' => '', //array
			'm' => '', //message
			'd' => '', //bolean // delete message
			'w' => '' // string id - komu
		];
		*/
		$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

		$redis_key = 'TELEGRAM:'.rand(0, 9999999);

		if($arr['d']):
			$del = 1;
		else: 
			$del = 0;
		endif;

		if($arr['b'] == 'bot'):
			$send_id = TELEGRAM_ID_SEND;
		
		elseif($arr['w'] ):
			$bot_id = TELEGRAM_ID_BOCIK_ROBOCIK;
			$send_id = $arr['w'];


		else:
			$user_sql = $DB->fetchAll("SELECT * FROM `USER` WHERE `name` = '".$arr['u']."' ")[0];
			$bot_id = TELEGRAM_ID_BOCIK_ROBOCIK;
			$send_id = $user_sql['TelegramID'];

		endif;

		$telegram = new Telegram($bot_id);


		if($arr['k']):
			foreach ($arr['k'] as $k =>  $line):
				foreach ($line as $key => $v):
					$option[$k][$key] = $telegram->buildInlineKeyboardButton($v[0], null, $v[1]);
				endforeach;
			endforeach;
			$keyboard = $telegram->buildInlineKeyBoard($option);
			$out['content'] = $content = array('parse_mode'=>'HTML', 'reply_markup' => $keyboard, 'chat_id' => $send_id, 'text' => $arr['m'], 'disable_web_page_preview' => true);

		else:
			$out['content'] = $content = array('parse_mode'=>'HTML', 'chat_id' => $send_id, 'text' => $arr['m'], 'disable_web_page_preview' => true);

		endif;

		$out = [
			'conf' => [
				'bot_id' => $bot_id,
				'send_id' => $send_id,
				'send_user' => $arr['u'],
				'redis_key' => $redis_key,
				'del' => $del,

			],
			'content' => $content,

		];

		$redis->set($redis_key, igbinary_serialize($out), 30);
		$out['exec'] = T::exec_send($redis_key);

		return $out;
	}



	static function exec_send($redis_key, $del_message_id = false){
		if($redis_key):
			$a = exec("php /var/www/bitbay/cli/telegram/v2.php ".$redis_key." > /dev/null 2>/dev/null &");
			return [
				'redis_key'=> $redis_key,
				'del_message_id' => $del_message_id,
			];
		endif;

	}


}