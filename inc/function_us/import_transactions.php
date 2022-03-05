<?


function import_year(){
	$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();
	$limit = 300;
	$p = unserialize($redis->get('import'));
	$year = $p['year'];
	$year_plus = $p['year'] + 1;

	$cursor = $p['nextPageCursor'];
	
	$API = new API(['public' => $p['publickey'], 'private' => $p['privatekey'], 'host' => 'api.zonda.exchange']);
	$res = $API->getTransactionsHistory($cursor, $limit, $year.'-01-01', $year_plus.'-01-01');

	/// add redis
	$parm = [
		'publickey' => $p['publickey'],
		'privatekey' => $p['privatekey'],
		'userID' => $p['userID'],
		'year' => $p['year'],
		'nextPageCursor' => $res['nextPageCursor'],
		'i' => ($p['i'] + 1), 
	];

	$redis->set('import', serialize($parm), 21600);
	///

	if($res['status'] == 'Ok'):
		
		if($res['items']):
			echo 'TRANSACTIONS userID: '.$p['userID'].' - '.$year.'<br>';

			foreach ($res['items'] as $k => $v):
				$add_sql = [
					'ID' => $v['ID'],
					'userID' => $p['userID'],
					'crypto' => $v['crypto'],
					'fiat' => $v['fiat'],
					'time' => $v['time'],
					'date' => $v['date'],
					'action' => $v['userAction'],
					'amount' => $v['amount'],
					'rate' => $v['rate'],
					'price' => $v['price'],
					'info' => 't',
				];
				$DB->insertOne('user_history', $add_sql, 'INSERT IGNORE ');
			endforeach;


			echo 'Pobieram: '.current($res['items'])['date'].' - '.end($res['items'])['date'].'<br>';
		endif;

		if($cursor != $res['nextPageCursor']):
			echo 'Wszystko ok - next page';
			return 'ok';

		else:
			echo 'Koniec pobierania historii z Bitbay ['.$year.']';

			$add_sql = [
				'userID' => $p['userID'],
				'year' => $p['year'],
				't' => 3,
				'allow' => 1,
				'info' => 'Skonczone pobieranie',
				'error' => '',
			];
			$DB->insertOrUpdate('request', $add_sql);

			$redis->del('import');
			return 'end';
		endif;

	else:
		echo 'error: status notok';
		$redis->del('import');

		$add_sql = [
			'userID' => $p['userID'],
			'year' => $p['year'],
			't' => 9,
			'allow' => 0,
			'info' => 'ERR-4',
			'error' => 1,
		];

		$DB->insertOrUpdate('request', $add_sql);

		print_r($res);
		return 'error';

	endif;

}
