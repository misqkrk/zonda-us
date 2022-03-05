

<div class="container">
	<div class="row">
			<div class="col col-12">
				<div class="box-1">
					<div class="welcome">Witaj, <?= $_SESSION[PROJECT]['AUTH']['username'];?></div>
					<h1>KARTA BINANCE - beta</h1>
					<h3>Wersja wczesna (Pamiętaj - to tylko rozliczenie karty binance!)</h3>

					<p>1. Pobierz historię karty binance na stronie [w xlsx] <a href="https://www.binance.com/en/cards/transaction" target="_blank">binance</a> </p>
					<p>2. Przekonwertuj pobrany plik xlsx (z binance - karta) na json na stronie <a href="https://www.aconvert.com/document/xlsx-to-json/" target="_blank">aconvert</a> </p>
					<p>3. Uploaduj plik json na tej stronie</p>
					<p>4. Gotowe</p>
					<br>

					<form method="post" enctype="multipart/form-data">
  Wgraj plik json
  <input type="file" name="fileToUpload" id="fileToUpload">
  <input type="submit" value="Wgraj" name="submit">
					</form>

<? 
// print_r($_POST);
// print_r($_FILES);

if($_POST && $_FILES):
	$type = $_FILES['fileToUpload']['type'];
	if($type == 'application/json'):
		$json = file_get_contents($_FILES["fileToUpload"]["tmp_name"]);
		$arr = json_decode($json, true);

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

		$content .= '<table>
		<tr><td>Opis</td><td>Kwota (EUR)</td><td>KURS EUR</td><td>KWOTA PLN</td><td>DATA</td><tr>';


				foreach ($arr as $key => $val):
					$date_timestamp = strtotime($val['Timestamp']);
					$nbps = $DB->fetchAll("SELECT * FROM `CURRENCY_nbp` WHERE `data` < '".date('Y-m-d', $date_timestamp)."' ORDER BY `data` DESC LIMIT 1", null, null, 600);
					$nbp = $nbps[0]['EUR'];

					if($val['Paid OUT (EUR)']):
						$price_eur = $val['Paid OUT (EUR)'];
						$action = 'Sell';
					elseif($val['Paid IN (EUR)']):
						$price_eur = 0 - $val['Paid IN (EUR)'];
						$action = 'Buy';
					endif;

					$sum_eur = $sum_eur + $price_eur;
					$sum_pln = $sum_pln + $price_pln;

					//$price_eur = $val['Paid OUT (EUR)'];
					$price_pln = $price_eur * $nbp;
					$content .=	'<tr class="'.($action=="Sell"?'c':'z').'">';
					//$content .=	'<td class="l">'.$action.'</td>';

					$content .=	'<td class="r">'.$val['Description'].'</td>';
					$content .=	'<td>'.my_number($price_eur).' EUR</td>';
					$content .=	'<td>'.$nbp .' PLN</td>';
					 $content .= '<td>'.my_number($price_pln).' PLN</td>';

					$content .=	'<td>'.date('Y-m-d H:i:s', $date_timestamp ).'</td>';
					$content .= '</tr>';
				endforeach;

		$content .= '<tr><td>SUMA</td><td>'.my_number($sum_eur).' EUR</td><td>---</td><td>'.my_number($sum_pln).' PLN</td><td>---</td><tr>';

		$content .= '</table>';
		$file_name = crc32($_SESSION[PROJECT]['AUTH']['username']).'_'.time().'.html';
		$file = '/var/www/us/results/binance/'.$file_name;


		$content_header ='<meta charset="utf-8"><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
		$gen = $content_header;
		$gen .= $content;
			file_put_contents($file, $gen);
		echo $content;

		echo '<br><a class="button-1" href="/results/binance/'.$file_name.'" target="_blank">Widok do wydruku</a>';

	else:
		echo 'Błędny format - wymagany json!';
	endif;
endif;





?>



				</div>
		</div>
	</div>
</div>