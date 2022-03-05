<?

function import_add(){
	$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

	$request = $DB->fetchAll("SELECT * FROM `request` WHERE (`t` = 1 OR `f` = 1) AND `allow` = 1 AND `error` IS NULL ORDER BY `year` DESC, `ID` ASC LIMIT 1")[0];
	$user = $DB->fetchAll("SELECT * FROM `username` WHERE `ID` = '".$request['userID']."'  ")[0];
	//print_r($request);
	//print_r($user);


	if($request): // jezeli jest coś do importowania
		if($request['year'] && $user['publickey'] && $user['privatekey']): // jeżeli są podstawowe dane
			$API = new API(['public' => $user['publickey'], 'private' => $user['privatekey'], 'host' => 'api.zonda.exchange']);
			$test_API = $API->getTransactionsHistory('start', 10, '2017-01-01', '2023-01-01');

			if($test_API['status'] == 'Ok'): // api ok

				$add_t = ($request['t'] == 1 ? 2 : $request['t']);
				$add_f = ($request['f'] == 1 ? 2 : $request['f']);

				$add_sql = [
					'userID' => $request['userID'],
					'year' => $request['year'],
					't' => $add_t,
					'f' => $add_f,
					'allow' => 1,
					'info' => 'Dodano do redis',
					'error' => '',
				];
			   $DB->insertOrUpdate('request', $add_sql);

				$add_redis = [
					'publickey' => $user['publickey'],
					'privatekey' => $user['privatekey'],
					'userID' => $request['userID'],
					'year' => $request['year'],
					'nextPageCursor' => "start",
				];
				
				$add_redis_fee = [
					'publickey' => $user['publickey'],
					'privatekey' => $user['privatekey'],
					'userID' => $request['userID'],
					'year' => $request['year'],
					'offset' => 0,
				];

				if($add_t == 2):
					$redis->set('import', serialize($add_redis), 43200);
				endif;

				if($add_f == 2):
					$redis->set('import_fee', serialize($add_redis_fee), 43200);
				endif;
				echo '<meta http-equiv="refresh" content="0;">';

			elseif($test_API['status'] == 'Fail'): // gdy bład api
				$add_sql = [
					'userID' => $request['userID'],
					'year' => $request['year'],
					't' => 9,
					'f' => 9,
					'allow' => 0,
					'info' => 'ERR-2 '.$test_API['errors'][0],
					'error' => 1,
				];
			   $DB->insertOrUpdate('request', $add_sql);
			   $redis->del('import');
			   $redis->del('import_fee');

			else: //inny bład api
				echo 'error 3';
				$add_sql = [
					'userID' => $request['userID'],
					'year' => $request['year'],
					't' => 9,
					'f' => 9,
					'allow' => 0,
					'info' => 'ERR-3 ',
					'error' => 1,
				];
			   $DB->insertOrUpdate('request', $add_sql);
			   $redis->del('import');
			   $redis->del('import_fee');

			endif;

			//print_r($test_API);
			//echo 'ok';
		else: // brak podstawowych danych
			echo 'error';
			print_r($request);
			$add_sql = [
				'userID' => $request['userID'],
				'year' => $request['year'],
				't' => 9,
				'allow' => 0,
				'info' => 'ERR-1. Niepoprawne dane',
				'error' => 1,
			];
		   $DB->insertOrUpdate('request', $add_sql); 
		   $redis->del('import');
		   $redis->del('import_fee');

		endif;

	else: // jezeli nie ma nic do importu
		echo 'T - nic do wykonania';
		
	endif;
}