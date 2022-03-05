<?
function report_generate($user = null, $year = 2018){
	$redis = $_ENV[PROJECT]['redis'] ?? Connect::redis();
	$DB = $_ENV[PROJECT]['DB'] ?? Connect::DB();

	$year_plus = $year + 1;

		$types = [
			'Sell' 	=> 'Sprzedaż',
			'Buy' 	=> 'Zakup',
			'Fee' 	=> 'Sprzedaż - prowizja',
		];

	$all = $DB->fetchAll("SELECT * FROM `user_history` WHERE `userID` = '".$user."' AND `date` >= '".$year."-01-01' AND `date` < '".$year_plus."-01-01' ORDER BY `date` DESC", null, null, null); 






	$tmpAll['sum'] = 0;
	$tmpAll['zakupy'] = 0;
	$tmpAll['sprzedaze'] = 0;
	$tmpAll['fee'] = 0;

	$tmpID = 0;
	foreach ($all as $k => $v):
			$nbps = $DB->fetchAll("SELECT * FROM `CURRENCY_nbp` WHERE `data` < '".date('Y-m-d', strtotime($v['date']))."' ORDER BY `data` DESC LIMIT 1", null, null, 600);
			$nbp = $nbps[0];

			$tmpCourse = $nbp[$v['fiat']];
			$tmpPricePLN = $v['price']*$tmpCourse;


			if($v['action'] == 'Buy'):
				$tmpAll['zakupy'] = $tmpAll['zakupy'] + $tmpPricePLN;
				$tmpAll['items_buy'] = $tmpAll['items_buy'] +1;
				//$tmpAll['zakupy'] = $tmpAll['zakupy'] + round($tmpPricePLN, 2);

				$tmpAdd['coin'][$v['crypto']]['items_buy'] = $tmpAdd['coin'][$v['crypto']]['items_buy'] + 1;
				$tmpAdd['coin'][$v['crypto']]['zakupy'] = $tmpAdd['coin'][$v['crypto']]['zakupy'] + $tmpPricePLN;


			elseif($v['action'] == 'Sell'):
				$tmpAll['sprzedaze'] = $tmpAll['sprzedaze'] + $tmpPricePLN;
				$tmpAll['items_sell'] = $tmpAll['items_sell'] +1;

				$tmpAdd['coin'][$v['crypto']]['items_sell'] = $tmpAdd['coin'][$v['crypto']]['items_sell'] + 1;
				$tmpAdd['coin'][$v['crypto']]['sprzedaze'] = $tmpAdd['coin'][$v['crypto']]['sprzedaze'] + $tmpPricePLN;


			elseif($v['action'] == 'Fee'):
				$tmpAll['fee'] = $tmpAll['fee'] + $tmpPricePLN;
				$tmpAll['items_fee'] = $tmpAll['items_fee'] +1;

				//$tmpAdd['coin'][$v['crypto']]['items_fee'] = $tmpAdd['coin'][$v['crypto']]['items_fee'] + 1;
				//$tmpAdd['coin'][$v['crypto']]['fee'] = $tmpAdd['coin'][$v['crypto']]['fee'] + $tmpPricePLN;

			endif;



			$tmpAll['items'][$tmpID]['course'] = $tmpCourse;
			$tmpAll['items'][$tmpID]['fiat'] = $v['fiat'];
			$tmpAll['items'][$tmpID]['crypto'] = $v['crypto'];
			$tmpAll['items'][$tmpID]['date'] = $v['date'];
			$tmpAll['items'][$tmpID]['action'] = $v['action'];
			$tmpAll['items'][$tmpID]['amount'] = $v['amount'];
			$tmpAll['items'][$tmpID]['rate'] = $v['rate'];
			$tmpAll['items'][$tmpID]['price'] = $v['price'];
			$tmpAll['items'][$tmpID]['pricepln'] = $tmpPricePLN;

		$tmpID++;
		$tmpAll['sum'] = $tmpAll['sum'] + $tmpPricePLN;

	endforeach;
	//print_r($tmpAll);

// echo '<pre>';
// print_r($tmpAdd);
// echo '</pre>';

	$add_sql = [
		'userID' => $user,
		'year' => $year,
		'g' => 3,
		'allow' => 1,
		'info' => 'Skonczone generowanie',
		'error' => '',
	];
	$DB->insertOrUpdate('request', $add_sql);


	$add_sql = [
		'userID' => $user,
		'year' => $year,
		'koszty' => $tmpAll['zakupy'],
		'przychody' => $tmpAll['sprzedaze'],
		'koszty_fee' => $tmpAll['fee'],
		'sum' => $tmpAll['sum'],
		'items_buy' => $tmpAll['items_buy'],
		'items_sell' => $tmpAll['items_sell'],
		'items_fee' => $tmpAll['items_fee'],
		'json' => json_encode($tmpAdd),

	];
	$DB->insertOrUpdate('results', $add_sql);

	$content .=
	'<style type="text/css">
@import url("https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300&display=swap");
table	{font-size: 12px; font-family: "Roboto Mono", monospace;}
td {padding: 2px 4px; height:10px;}
@media print {
  table {font-size: 10px;}
  td {padding: 1px 3px; height:10px;}
}
table { border-collapse: collapse;}
table, th, td {border: 1px solid black;}
td  {text-align: right;}
.l  {text-align: left;}
.c {color: #008206;}
.z {color: #ab0101;}
</style>';

	$content .='<meta charset="utf-8">';
	$content .='<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
	$content .=
	'<table>
		<tr><td>Typ</td><td>Ilość</td><td>Kurs<br>Kryptowaluty</td><td>Waluta</td><td>Kurs<br> waluty</td><td>Cena</td><td>Cena PLN</td><td>Data</td></tr>';
			
				foreach ($tmpAll['items'] as $key => $val):
					$content .=	'<tr class="'.($val['action']=="Sell"?'c':'z').'">';
					$content .=	'<td class="l">'.$types[$val['action']].' '.$val['crypto'].'</td>';
					$content .=	'<td>'.($val['amount']?$val['amount'] .' '.$val['crypto']: '').'</td>';
					$content .=	'<td>'.($val['rate']? my_number($val['rate']).' '.$val['fiat']: '' ).'</td>';
					$content .= '<td>'.$val['fiat'].'</td>';
					$content .= '<td>'.$val['course'].'</td>';
					$content .=	'<td>'.number_format($val['price'],2,'.',' ').' '.$val['fiat'].'</td>';
					$content .=	'<td>'.number_format($val['pricepln'],2,'.',' ').'</td>';
					$content .=	'<td>'.$val['date'].'</td>';
					$content .= '</tr>';
				endforeach;
				
		$content .=	'<td><b>SUMA</b></td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td><b>'.my_number($tmpAll['sum']).'</b></td><td>-</td>';
		$content .= '</table>';


		$content .= '<br>';
		$content .= '<table>
	<tr><td class="l"></td><td>KOSZTY</td><td>PRZYCHÓD</td><td>DOCHÓD</td></tr>
	<tr><td class="l">KRYPTOWALUTY</td><td>'.my_number($tmpAll['zakupy']).' PLN</td> <td>'.my_number($tmpAll['sprzedaze']).' PLN</td><td></td></tr>
	<tr><td class="l">PROWIZJE</td><td>'.my_number($tmpAll['fee']).' PLN</td><td>N/A</td><td></td></tr>
	<tr><td class="l">SUMA</td><td>'.my_number($tmpAll['zakupy'] + $tmpAll['fee']).' PLN</td><td>'.my_number($tmpAll['sprzedaze']).' PLN</td><td>'.my_number($tmpAll['sum']).' PLN</td></tr>
</table>
		';


		$file = '../results/'.crc32($user).'_'.$year.'.html';



	echo '<html>';
	echo '<style type="text/css">
@import url("https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@300&display=swap");
table	{font-size: 10px; font-family: "Roboto Mono", monospace;}
table { border-collapse: collapse;}
td {padding: 1px 3px; height:10px;}
table, th, td {border: 1px solid black;}
td  {text-align: right;}
.l  {text-align: left;}
.c {color: #008206;}
.z {color: #ab0101;}
</style>';
		echo '<body>';

echo '
<table>
	<tr><td class="l"></td><td>KOSZTY</td><td>PRZYCHÓD</td><td>DOCHÓD</td></tr>
	<tr><td class="l">KRYPTOWALUTY</td><td>'.my_number($tmpAll['zakupy']).' PLN</td> <td>'.my_number($tmpAll['sprzedaze']).' PLN</td><td></td></tr>
	<tr><td class="l">PROWIZJE</td><td>'.my_number($tmpAll['fee']).' PLN</td><td>N/A</td><td></td></tr>
	<tr><td class="l">SUMA</td><td>'.my_number($tmpAll['zakupy'] + $tmpAll['fee']).' PLN</td><td>'.my_number($tmpAll['sprzedaze']).' PLN</td><td>'.my_number($tmpAll['sum']).' PLN</td></tr>
</table>
';
		echo '<hr>';
		echo 'Pobierz <a target="_blank" href="/results/'.crc32($user).'_'.$year.'.html">'.crc32($user).' '.$year.'</a>';
		echo '</body></html>';
		//echo $content;
		//echo $content;
		file_put_contents($file, $content);
}
