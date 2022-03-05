<?
 include_once '../loader.php';
$od = $_GET['od'] ?? '2021-06-01';
$do = $_GET['do'] ?? '2021-12-07';
echo 'Pobieranie Kursów NBP: od '.$od.' do '.$do.PHP_EOL;

$import = Curl::single('http://api.nbp.pl/api/exchangerates/rates/a/USD/'.$od.'/'.$do.'/?format=json',true);
$arr = $import['rates'];

foreach ($arr as $k => $v):
	$q = [
		'data' => $v['effectiveDate'],
		'USD' => $v['mid'],
	];
	$DB->insertOrUpdate('CURRENCY_nbp', $q);

endforeach;

$import = Curl::single('http://api.nbp.pl/api/exchangerates/rates/a/GBP/'.$od.'/'.$do.'/?format=json',true);
$arr = $import['rates'];

foreach ($arr as $k => $v):
	$q = [
		'data' => $v['effectiveDate'],
		'GBP' => $v['mid'],
	];
	$DB->insertOrUpdate('CURRENCY_nbp', $q);

endforeach;

$import = Curl::single('http://api.nbp.pl/api/exchangerates/rates/a/EUR/'.$od.'/'.$do.'/?format=json',true);
$arr = $import['rates'];

foreach ($arr as $k => $v):
	$q = [
		'data' => $v['effectiveDate'],
		'EUR' => $v['mid'],
	];
	$DB->insertOrUpdate('CURRENCY_nbp', $q);

endforeach;