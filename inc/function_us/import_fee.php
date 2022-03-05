<?

function import_fee(){
	$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
	$limit = 200;

	$p = unserialize($redis->get('import_fee'));
	$year = $p['year'];
	$year_plus = $p['year'] + 1;

	$offset = $p['offset'];
	$API = new API(['public' => $p['publickey'], 'private' => $p['privatekey'], 'host' => 'api.zonda.exchange']);
	$res = $API->getHistoryFee($offset, $limit, $year.'-01-01', $year_plus.'-01-01');
	
	if($res['status'] == 'Ok'):
		echo 'FEE userID: '.$p['userID'].' - '.$year.'<br>';
		
		if($res['items'][0]['date']):
			echo 'Pobieram: '.current($res['items'])['date'].' - '.end($res['items'])['date'].'<br>';
		endif;

		//print_r($res);

		$parm = [
			'publickey' => $p['publickey'],
			'privatekey' => $p['privatekey'],
			'userID' => $p['userID'],
			'year' => $p['year'],
			'offset' => $offset += $limit,
			'i' => ($p['i'] + 1), 
		];

		$redis->set('import_fee', serialize($parm), 120000);

		if($res['fetchedRows'] > 0):

			foreach ($res['items'] as $k => $v):
				$add_sql = [
					'ID' => $v['historyId'],
					'userID' => $p['userID'],
					'crypto' => '',
					'fiat' => $v['currency'],
					'time' => $v['time'],
					'date' => $v['date'],
					'action' => 'Fee',
					'amount' => '',
					'rate' => '',
					'price' => $v['value'],
					'info' => 'f',
				];
				$DB->insertOne('user_history', $add_sql, 'INSERT IGNORE ');
			endforeach;

		else:
			echo 'Koniec pobierania prowizji z Bitbay ['.$year.']';
			$redis->del('import_fee');
				$add_sql = [
					'userID' => $p['userID'],
					'year' => $p['year'],
					'f' => 3,
					'allow' => 1,
					'info' => 'Skonczone pobieranie',
					'error' => '',
				];
			   $DB->insertOrUpdate('request', $add_sql);

		endif;

	else:
		echo 'error: status notok';
		$redis->del('import');

		$add_sql = [
			'userID' => $p['userID'],
			'year' => $p['year'],
			'f' => 9,
			'allow' => 0,
			'info' => 'ERR-4',
			'error' => 1,
		];

		$DB->insertOrUpdate('request', $add_sql);

		print_r($res);
		return 'error';
	endif;
}
