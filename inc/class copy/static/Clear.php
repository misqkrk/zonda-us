<?

class Clear {
	static function B2(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

			$DB->execute("DELETE
				FROM
				B2_buy
				WHERE
					date_start < NOW() - INTERVAL 48 HOUR
					AND `code` = 'close'
					AND receivedAmount = 0
			");
	}

	static function B3(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

			$DB->execute("DELETE
				FROM
				B3_sell
				WHERE
					date_start < NOW() - INTERVAL 48 HOUR
					AND `code` = 'close'
					AND receivedAmount = 0
			");
	}


	static function balance(){
		$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

			$DB->execute("
				DELETE FROM account_balance  WHERE id NOT IN (
				SELECT * FROM (
				  SELECT MAX(ID) as id 
				  FROM account_balance 
				  WHERE (last > '2021-01-01 00:00:00')
				  GROUP BY DATE(last)
				) AS TAB) AND last < '".timestamp_to_date( strtotime('-3 day',strtotime('now')) )."'
			");
	}
}